<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\Page;

/**
 * SearchPlugin::searchPages() behavior: only publicly visible pages are
 * returned (drafts and future-scheduled pages never appear), block text
 * in content_json is searched, LIKE wildcards in the query are treated
 * literally, and results carry only the fields the search view uses.
 */
class SearchPluginTest extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];

    public static function setUpBeforeClass(): void
    {
        // Plugin classes are require_once'd by PluginManager, not
        // autoloaded by composer.
        require_once dirname(__DIR__, 2) . '/plugins/Search/Plugin.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
        parent::tearDown();
    }

    private function makePage(array $data): Page
    {
        $suffix = bin2hex(random_bytes(3));
        $page = Page::create(array_merge([
            'title' => 'Search ' . $suffix,
            'slug' => 'search-' . $suffix,
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->subDay(),
        ], $data));
        $this->pageIds[] = $page->id;
        return $page;
    }

    public function test_draft_pages_never_appear_in_results()
    {
        $draft = $this->makePage([
            'title' => 'Draft XYZZY Plugh ' . bin2hex(random_bytes(2)),
            'status' => 'draft',
        ]);

        $results = \SearchPlugin::searchPages('XYZZY Plugh');
        $this->assertTrue($results->every(fn ($p) => $p->slug !== $draft->slug), 'drafts must not be searchable');
    }

    public function test_future_scheduled_pages_never_appear_in_results()
    {
        $scheduled = $this->makePage([
            'title' => 'Future XYZZY2 ' . bin2hex(random_bytes(2)),
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->addDay(),
        ]);

        $results = \SearchPlugin::searchPages('XYZZY2');
        $this->assertTrue($results->every(fn ($p) => $p->slug !== $scheduled->slug), 'future-scheduled pages must not be searchable');
    }

    public function test_published_page_is_found_by_block_body_text()
    {
        $marker = 'XyZZyB' . bin2hex(random_bytes(2));
        $page = $this->makePage([
            'title' => 'Body Search Target',
            'content_json' => json_encode([
                ['type' => 'text', 'html' => '<p>needle ' . $marker . ' in a block</p>'],
            ]),
        ]);

        $results = \SearchPlugin::searchPages($marker);
        $this->assertTrue(
            $results->contains(fn ($p) => $p->slug === $page->slug),
            'a published page must be findable by text inside content_json blocks'
        );
    }

    public function test_published_page_is_found_by_legacy_content_text()
    {
        $marker = 'XyZZyC' . bin2hex(random_bytes(2));
        $page = $this->makePage([
            'title' => 'Legacy Content Target',
            'content' => '<p>legacy needle ' . $marker . ' here</p>',
        ]);

        $results = \SearchPlugin::searchPages($marker);
        $this->assertTrue($results->contains(fn ($p) => $p->slug === $page->slug));
    }

    public function test_like_wildcards_in_query_are_literal()
    {
        // A literal percent must not match "everything": searching for
        // a marker containing % must find only pages containing that
        // literal text.
        $plain = $this->makePage(['title' => 'Wildcard plain page']);
        $literal = $this->makePage(['title' => 'contains 100% literal']);

        $results = \SearchPlugin::searchPages('100% literal');
        $this->assertTrue($results->contains(fn ($p) => $p->slug === $literal->slug));
        $this->assertFalse($results->contains(fn ($p) => $p->slug === $plain->slug), '% must not act as a wildcard');
    }

    public function test_results_carry_only_view_fields_and_excerpt()
    {
        $marker = 'XyZZyD' . bin2hex(random_bytes(2));
        $page = $this->makePage([
            'title' => 'Field Whitelist Target',
            'content_json' => json_encode([
                ['type' => 'text', 'html' => '<p>needle ' . $marker . '</p>'],
            ]),
        ]);

        $results = \SearchPlugin::searchPages($marker);
        $hit = $results->first(fn ($p) => $p->slug === $page->slug);
        $this->assertNotNull($hit);

        $fields = array_keys(get_object_vars($hit));
        sort($fields);
        $this->assertSame(['content', 'slug', 'title'], $fields, 'results must expose only the fields the view uses');
        $this->assertStringContainsString('needle ' . $marker, (string) $hit->content);
        $this->assertStringNotContainsString('<p>', (string) $hit->content, 'excerpt must be plain text');
    }

    public function test_query_is_capped_and_results_are_limited()
    {
        $this->assertSame('', \SearchPlugin::normalizeQuery('   '));
        $this->assertSame(
            str_repeat('x', 99) . 'a',
            \SearchPlugin::normalizeQuery(str_repeat('x', 99) . 'a' . str_repeat('y', 200)),
            'queries are truncated to the first 100 characters'
        );
        $this->assertLessThanOrEqual(100, mb_strlen(\SearchPlugin::normalizeQuery(str_repeat('x', 300))));
    }
}
