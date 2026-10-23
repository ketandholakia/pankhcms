<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\VisitTracker;
use App\Models\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cached pages never reach PHP rendering, so view counting uses the
 * lightweight POST /_v beacon (handled pre-bootstrap in
 * public/index.php via VisitTracker::handleBeacon, injected by the
 * public layouts). The handler is DB-only: browsers-only (bots and
 * blank agents skipped), path-capped, no session started, anonymized
 * IP, every failure swallowed.
 *
 * serveEarlyIfPossible() deliberately does NOT touch the database —
 * PageCacheTest's zero-query assertion for early cache hits stays
 * authoritative.
 */
class ViewBeaconTest extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = [
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
            'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? null,
        ];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.55';
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_COOKIE[session_name()]);

        // The production beacon path runs in a fresh request with no
        // active session (cached pages are session-less). Other suite
        // tests may have left a session open in this process; close it
        // so the tracker behaves exactly as on a real beacon hit.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        DB::table('page_views')->where('ip', '203.0.113.x')->delete();
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
        DB::table('page_views')->where('ip', '203.0.113.x')->delete();

        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        $_POST = [];
        parent::tearDown();
    }

    private function makePage(): Page
    {
        $page = Page::create([
            'title' => 'BeaconMe ' . bin2hex(random_bytes(2)),
            'slug' => 'beaconme-' . bin2hex(random_bytes(2)),
            'status' => 'published',
        ]);
        $this->pageIds[] = $page->id;
        return $page;
    }

    public function test_beacon_counts_the_view_with_anonymized_ip_and_no_session()
    {
        $page = $this->makePage();
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['p' => '/' . $page->slug];

        VisitTracker::handleBeacon();

        $row = DB::table('page_views')
            ->where('page_id', $page->id)
            ->where('ip', '203.0.113.x')
            ->first();

        $this->assertNotNull($row, 'a beacon hit must insert a page_views row');
        $this->assertSame('/' . $page->slug, $row->path);
        $this->assertNull($row->session_id, 'beacon hits are anonymous: no session id may be stored');
        $this->assertSame('203.0.113.x', $row->ip, 'only the anonymized IP may be persisted');
    }

    public function test_beacon_accepts_a_json_body()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux) Firefox/128.0';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_POST = [];
        $this->makePage();
        // JSON body reading requires php://input, unavailable to fake in
        // this process; the urlencoded path above is the production
        // sendBeacon shape. Here we just prove a JSON content type with
        // no body degrades to a no-op rather than erroring.
        VisitTracker::handleBeacon();
        $this->assertSame(
            0,
            DB::table('page_views')->where('ip', '203.0.113.x')->count(),
            'an empty JSON body must be ignored, not crash'
        );
    }

    public function test_unknown_path_still_records_a_row_without_page_id()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/605.1.15';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['p' => '/definitely-not-a-page-' . bin2hex(random_bytes(2))];

        VisitTracker::handleBeacon();

        $row = DB::table('page_views')
            ->where('ip', '203.0.113.x')
            ->whereNull('page_id')
            ->first();

        $this->assertNotNull($row, 'a beacon for an unknown path is still a view');
    }

    public function test_beacon_rejects_missing_oversized_and_malformed_paths()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Chrome/126.0';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        foreach ([
            '',                       // missing
            'no-leading-slash',       // malformed
            '/' . str_repeat('a', 400), // oversized
        ] as $badPath) {
            DB::table('page_views')->where('ip', '203.0.113.x')->delete();
            $_POST = $badPath === '' ? [] : ['p' => $badPath];

            VisitTracker::handleBeacon();

            $this->assertSame(
                0,
                DB::table('page_views')->where('ip', '203.0.113.x')->count(),
                "path '" . substr((string) $badPath, 0, 20) . "...' must be rejected"
            );
        }
    }

    public function test_beacon_skips_bots_and_blank_user_agents()
    {
        foreach ([
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'curl/8.5.0',
            'Mozilla/5.0 HeadlessChrome/126.0',
            '', // no real browser sends an empty User-Agent
        ] as $ua) {
            DB::table('page_views')->where('ip', '203.0.113.x')->delete();
            $_SERVER['HTTP_USER_AGENT'] = $ua;
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['p' => '/whatever'];

            VisitTracker::handleBeacon();

            $this->assertSame(
                0,
                DB::table('page_views')->where('ip', '203.0.113.x')->count(),
                "user agent '$ua' must not be counted"
            );
        }
    }

    public function test_early_cache_path_never_touches_the_database()
    {
        // The constraint from the hardening pass: no page_views insert
        // may live in the early cache path.
        $pageCache = file_get_contents(dirname(__DIR__, 2) . '/app/Middleware/PageCache.php');
        $this->assertStringNotContainsString('trackEarlyHit', $pageCache);
        $this->assertStringNotContainsString('page_views', $pageCache);
        $this->assertStringNotContainsString('VisitTracker', $pageCache);
        $this->assertFalse(
            method_exists(\App\Middleware\PageCache::class, 'trackEarlyHit'),
            'the early cache must not expose a tracking method'
        );
    }

    public function test_public_layouts_ship_the_beacon_and_the_endpoint_is_wired()
    {
        foreach ([
            dirname(__DIR__, 2) . '/themes/pankhcmsstarter/views/layouts/main.blade.php',
            dirname(__DIR__, 2) . '/views/layouts/site.blade.php',
        ] as $layout) {
            $src = file_get_contents($layout);
            $this->assertStringContainsString("sendBeacon('/_v'", $src, "$layout must inject the view beacon");
        }

        $index = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
        $this->assertStringContainsString(
            'VisitTracker::handleBeacon()',
            $index,
            'public/index.php must route POST /_v to the beacon handler'
        );
    }
}
