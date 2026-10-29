<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\RedirectController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Phase 2 redirect hardening: validation of redirect pairs before they
 * are stored. Threat model — an admin (CSRF-protected, manage_settings)
 * cannot accidentally or deliberately create header-injecting,
 * open-redirecting, self-referential, cycling, internal-route-shadowing
 * or malformed redirects.
 */
class RedirectHardeningTest extends TestCase
{
    private array $redirectIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        $_ENV['APP_URL'] = 'http://example.test';
        putenv('APP_URL=http://example.test');
        putenv('ALLOWED_HOSTS=');
    }

    protected function tearDown(): void
    {
        foreach ($this->redirectIds as $id) {
            DB::table('redirects')->where('id', $id)->delete();
        }
        $this->redirectIds = [];
        unset($_ENV['APP_URL']);
        putenv('APP_URL');
        putenv('ALLOWED_HOSTS');
        parent::tearDown();
    }

    private function validate(string $from, string $to, ?int $ignoreId = null)
    {
        return RedirectController::validatePair($from, $to, $ignoreId);
    }

    private function assertError($result, string $needle, string $message): void
    {
        $this->assertIsString($result, $message);
        $this->assertStringContainsString($needle, $result);
    }

    /* -------------------------------------------------- valid pairs pass */

    public function test_valid_same_site_paths_pass_and_are_normalized()
    {
        $result = $this->validate('/old-page', '/new-page');
        $this->assertIsArray($result);
        $this->assertSame(['/old-page', '/new-page'], $result);
    }

    public function test_trailing_slashes_are_normalized()
    {
        $result = $this->validate('/old-page/', '/new-page/');
        $this->assertSame(['/old-page', '/new-page'], $result);
    }

    public function test_self_site_absolute_target_is_allowed()
    {
        $result = $this->validate('/old', 'http://example.test/new');
        $this->assertIsArray($result, 'an absolute URL on this site must be allowed');
        $this->assertSame(['/old', 'http://example.test/new'], $result);
    }

    public function test_allowlist_env_extends_allowed_absolute_targets()
    {
        putenv('ALLOWED_HOSTS=partner.example');
        $result = $this->validate('/old', 'https://partner.example/landing');
        $this->assertIsArray($result);
        putenv('ALLOWED_HOSTS');
    }

    /* --------------------------------------------- malformed input fails */

    public function test_control_characters_in_target_are_rejected()
    {
        // CRLF header injection attempt.
        $this->assertError(
            $this->validate('/old', "/new\r\nSet-Cookie: injected=1"),
            'control characters',
            'CRLF in the target must be rejected'
        );

        // Lone newline.
        $this->assertError($this->validate('/old', "/new\nmore"), 'control characters', 'newline rejected');

        // Null byte.
        $this->assertError($this->validate("/old", "/new\x00bad"), 'control characters', 'null byte rejected');
    }

    public function test_control_characters_in_from_are_rejected()
    {
        $this->assertError($this->validate("/old\r\nx", '/new'), 'control characters', 'CRLF in from rejected');
    }

    public function test_protocol_relative_targets_are_rejected()
    {
        $this->assertError($this->validate('/old', '//evil.example/steal'), 'same-site path', '//host is an open redirect');
    }

    public function test_foreign_absolute_targets_are_rejected()
    {
        $this->assertError($this->validate('/old', 'https://evil.example/steal'), 'same-site path', 'foreign host rejected');
        $this->assertError($this->validate('/old', 'http://other.example/steal'), 'same-site path', 'foreign host rejected');
    }

    public function test_dangerous_schemes_are_rejected()
    {
        foreach ([
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox',
            'file:///etc/passwd',
        ] as $target) {
            $this->assertError($this->validate('/old', $target), 'same-site path', "$target must be rejected");
        }
    }

    public function test_empty_inputs_are_rejected()
    {
        $this->assertError($this->validate('', '/new'), 'required', 'empty from rejected');
        $this->assertError($this->validate('/old', ''), 'required', 'empty to rejected');
    }

    /* -------------------------------------------- self-redirects & cycles */

    public function test_exact_self_redirect_is_rejected()
    {
        $this->assertError($this->validate('/loop', '/loop'), 'cannot point at itself', 'self redirect rejected');
    }

    public function test_two_node_cycle_is_detected_against_existing_rows()
    {
        DB::table('redirects')->insert(['old_url' => '/b', 'new_url' => '/a', 'type' => 301]);
        $existingId = (int) DB::getPdo()->lastInsertId();
        $this->redirectIds[] = $existingId;

        // /a -> /b while /b -> /a already exists: a two-node loop.
        $this->assertError(
            $this->validate('/a', '/b'),
            'creates a loop',
            'closing a two-node cycle must be rejected'
        );
    }

    public function test_multi_step_cycles_are_detected()
    {
        // Existing chain: /b -> /c -> /a. Adding /a -> /b closes a 3-loop.
        DB::table('redirects')->insert(['old_url' => '/b', 'new_url' => '/c', 'type' => 301]);
        $this->redirectIds[] = (int) DB::getPdo()->lastInsertId();
        DB::table('redirects')->insert(['old_url' => '/c', 'new_url' => '/a', 'type' => 301]);
        $this->redirectIds[] = (int) DB::getPdo()->lastInsertId();

        $this->assertError($this->validate('/a', '/b'), 'creates a loop', 'three-node cycle rejected');
    }

    public function test_chains_without_cycles_are_allowed()
    {
        DB::table('redirects')->insert(['old_url' => '/b', 'new_url' => '/final', 'type' => 301]);
        $this->redirectIds[] = (int) DB::getPdo()->lastInsertId();

        // /a -> /b -> /final: a chain, not a cycle — allowed.
        $result = $this->validate('/a', '/b');
        $this->assertIsArray($result, 'a non-circular chain must be allowed');
    }

    public function test_cycle_check_ignores_the_row_being_updated()
    {
        $id = $this->makeRedirect('/keep', '/elsewhere', 301);

        // Updating THIS row must not see its own old_url as a cycle node.
        $result = $this->validate('/keep', '/other', $id);
        $this->assertIsArray($result, 'updating a row must ignore that row in cycle detection');
    }

    private function makeRedirect(string $from, string $to, int $type = 301): int
    {
        DB::table('redirects')->insert(['old_url' => $from, 'new_url' => $to, 'type' => $type]);
        $id = (int) DB::getPdo()->lastInsertId();
        $this->redirectIds[] = $id;
        return $id;
    }

    /** Build a linear chain /chain-0 -> /chain-1 -> ... -> /chain-$rows (rows redirects). */
    private function makeChain(int $rows): void
    {
        for ($i = 0; $i < $rows; $i++) {
            $this->makeRedirect('/chain-' . $i, '/chain-' . ($i + 1));
        }
    }

    /* ------------------------------------------------ reserved segments */

    public function test_reserved_internal_routes_are_rejected_segment_exact()
    {
        foreach ([
            '/admin', '/admin/users',
            '/install', '/install/step',
            '/api', '/api/v1/pages',
            '/_v',
            '/themes', '/themes/pankhcmsstarter/x',
            '/storage', '/storage/logs',
            '/assets', '/assets/css/site.css',
            '/uploads', '/uploads/media/a.png',
        ] as $reserved) {
            $this->assertError(
                $this->validate($reserved, '/new'),
                'collides with the internal',
                "reserved path '$reserved' must be rejected as a from-path"
            );
        }
    }

    public function test_segment_boundary_does_not_block_unrelated_paths()
    {
        // "/administrator" is NOT "/admin" — must stay allowed.
        $result = $this->validate('/administrator', '/new');
        $this->assertIsArray($result, '/administrator must not be treated as an /admin collision');

        // "/api-docs" is not "/api"; "_v2" is not "_v".
        $this->assertIsArray($this->validate('/api-docs', '/new'));
        $this->assertIsArray($this->validate('/_v2', '/new'));
    }

    /* ------------------------------------------- lookup behavior remains */

    public function test_match_ignores_query_string_and_still_resolves()
    {
        $id = $this->makeRedirect('/campaign', '/landing', 301);

        $match = RedirectController::matchFor('/campaign?utm_source=newsletter');
        $this->assertNotNull($match, 'query strings must not break matching');
        $this->assertSame('/landing', $match[0]);

        DB::table('redirects')->where('id', $id)->delete();
    }

    public function test_double_encoded_paths_do_not_match_their_plain_form()
    {
        // A stored plain from-path must not be matched by its
        // double-encoded variant (and vice versa) — no surprise hops.
        $this->makeRedirect('/a%2Fb', '/plain-target', 301);

        $this->assertNull(
            RedirectController::matchFor('/a/b'),
            'the encoded from-path must not shadow the decoded path'
        );
        DB::table('redirects')->where('old_url', '/a%2Fb')->delete();
    }

    /* ------------------------------------ fail-closed chain traversal (P5) */

    public function test_runtime_single_hop_redirects_to_target_with_status()
    {
        $this->makeRedirect('/one-hop', '/destination', 301);
        $match = RedirectController::matchFor('/one-hop');
        $this->assertSame(['/destination', 301], $match);
    }

    public function test_runtime_two_hop_acyclic_chain_resolves_to_final_target()
    {
        $this->makeRedirect('/hop-a', '/hop-b');
        $this->makeRedirect('/hop-b', '/final-page');

        $match = RedirectController::matchFor('/hop-a');
        $this->assertSame(['/final-page', 301], $match, 'runtime must resolve the FULL chain, not the first hop');
    }

    public function test_runtime_legacy_self_reference_is_refused()
    {
        $this->makeRedirect('/legacy-self', '/legacy-self', 301);
        $this->assertNull(RedirectController::matchFor('/legacy-self'), 'a legacy self-redirect must be refused, not looped');
    }

    public function test_runtime_legacy_two_node_cycle_is_refused()
    {
        $this->makeRedirect('/cyc-a', '/cyc-b');
        $this->makeRedirect('/cyc-b', '/cyc-a');
        $this->assertNull(RedirectController::matchFor('/cyc-a'), 'legacy two-node cycle must be refused');
        $this->assertNull(RedirectController::matchFor('/cyc-b'));
    }

    public function test_runtime_legacy_three_node_cycle_is_refused()
    {
        $this->makeRedirect('/c3-a', '/c3-b');
        $this->makeRedirect('/c3-b', '/c3-c');
        $this->makeRedirect('/c3-c', '/c3-a');
        $this->assertNull(RedirectController::matchFor('/c3-b'), 'legacy three-node cycle must be refused');
    }

    public function test_runtime_cycle_beyond_the_traversal_limit_is_refused()
    {
        // 12-node ring: longer than the 10-hop traversal bound.
        for ($i = 0; $i < 12; $i++) {
            $this->makeRedirect('/ring-' . $i, '/ring-' . (($i + 1) % 12));
        }
        $this->assertNull(RedirectController::matchFor('/ring-0'), 'cycles beyond the hop bound must fail closed');
    }

    public function test_runtime_acyclic_chain_at_the_traversal_limit_resolves()
    {
        // Exactly 10 redirects: /c-0 -> /c-1 -> ... -> /c-10 (terminal URL).
        $this->makeChain(10);

        $match = RedirectController::matchFor('/chain-0');
        $this->assertNotNull($match, 'a chain at the hop limit must still resolve');
        $this->assertSame('/chain-10', $match[0]);
    }

    public function test_runtime_acyclic_chain_beyond_the_limit_fails_closed()
    {
        // 12 redirects: resolving /chain2-0 would need 12 hops > 10.
        for ($i = 0; $i < 12; $i++) {
            $this->makeRedirect('/chain2-' . $i, '/chain2-' . ($i + 1));
        }

        $this->assertNull(
            RedirectController::matchFor('/chain2-0'),
            'a chain beyond the hop bound must fail closed rather than half-redirect'
        );
    }

    public function test_validation_rejects_pairs_extending_a_chain_beyond_the_limit()
    {
        // 10 existing redirects ending at /chain-end: adding /new -> /chain-end
        // would make an 11-hop chain — rejected at save time.
        $this->makeChain(10);
        $this->makeRedirect('/chain-end', '/final-dest');

        $this->assertError(
            $this->validate('/new-start', '/chain-0'),
            'exceeds the maximum length',
            'a pair that extends a chain beyond the limit must be rejected'
        );
    }

    public function test_validation_allows_pairs_within_the_limit()
    {
        $this->makeChain(8);
        $result = $this->validate('/new-start', '/chain-0');
        $this->assertIsArray($result, 'a pair keeping the chain within the limit must be allowed');
    }

    public function test_validation_rejects_update_that_introduces_a_cycle()
    {
        $a = $this->makeRedirect('/up-a', '/terminal');
        $b = $this->makeRedirect('/up-b', '/up-a');

        // Retarget /up-a to /up-b: /up-a -> /up-b -> /up-a = cycle.
        // (ignoreId excludes row A itself from the walk.)
        $result = $this->validate('/up-a', '/up-b', $a);
        $this->assertError($result, 'creates a loop', 'an update that introduces a cycle must be rejected');
    }

    public function test_runtime_legacy_empty_target_is_refused()
    {
        $this->makeRedirect('/legacy-empty', '');
        $this->assertNull(RedirectController::matchFor('/legacy-empty'), 'legacy empty-target rows must be refused');
    }

    public function test_runtime_preserves_301_and_302_entry_status()
    {
        $id301 = $this->makeRedirect('/perm', '/perm-final');
        $match301 = RedirectController::matchFor('/perm');
        $this->assertSame(301, $match301[1]);
        DB::table('redirects')->where('id', $id301)->update(['type' => 302]);
        $match302 = RedirectController::matchFor('/perm');
        $this->assertSame(302, $match302[1], '302 entry status must be preserved through the chain');
    }
}
