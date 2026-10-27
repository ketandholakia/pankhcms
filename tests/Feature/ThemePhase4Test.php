<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\RedirectController;
use App\Core\Bootstrap;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Phase 4: taxonomy archives, RSS feed, JSON-LD/og:type SEO, themed 404,
 * and the redirects manager (runtime + admin CRUD).
 */
class ThemePhase4Test extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];
    private array $termIds = [];
    private array $termPageLinks = [];
    private array $redirectIds = [];
    private static bool $routesLoaded = false;

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
        $loadRoutes = new \ReflectionMethod(Bootstrap::class, 'loadRoutes');
        $loadRoutes->invoke(null);
        \Flight::router()->rewind();
        self::$routesLoaded = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200)->clearBody();
        $_GET = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->termPageLinks as $pair) {
            DB::table($pair[0])->where($pair[1])->delete();
        }
        foreach ($this->termIds as $id) {
            DB::table('categories')->where('id', $id)->delete();
            DB::table('tags')->where('id', $id)->delete();
        }
        foreach ($this->pageIds as $id) { Page::find($id)?->delete(); }
        foreach ($this->redirectIds as $id) {
            DB::table('redirects')->where('id', $id)->delete();
        }
        $this->pageIds = []; $this->termIds = []; $this->termPageLinks = []; $this->redirectIds = [];
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function render(string $view, array $data): string
    {
        return (string) \Flight::get('blade')->render($view, $data);
    }

    private function runRoute(string $method, string $url): string
    {
        $request = new \flight\net\Request();
        $request->url = $url;
        $request->method = $method;
        $request->query = new \flight\util\Collection($_GET);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        \Flight::router()->rewind();

        $route = \Flight::router()->route($request);
        $this->assertNotFalse($route, "route $url must be registered");

        ob_start();
        try {
            call_user_func_array($route->callback, array_values($route->params));
        } finally {
            return (string) ob_get_clean();
        }
    }

    private function makePage(array $data = []): Page
    {
        $page = Page::create(array_merge([
            'title' => 'Post ' . bin2hex(random_bytes(2)),
            'slug' => 'p4-' . bin2hex(random_bytes(3)),
            'status' => 'published',
            'type' => 'post',
            'published_at' => \Illuminate\Support\Carbon::now()->subDay(),
        ], $data));
        $this->pageIds[] = $page->id;
        return $page;
    }

    private function attachTerm(string $table, string $pageTable, string $foreignKey, int $termId, int $pageId): void
    {
        DB::table($table)->insert([$foreignKey => $termId, 'page_id' => $pageId]);
        $this->termPageLinks[] = [$table, [$foreignKey => $termId, 'page_id' => $pageId]];
    }

    /* -------------------------------------------------------- archives */

    public function test_category_archive_lists_visible_pages_for_the_term()
    {
        $category = Category::create(['name' => 'P4Cat ' . bin2hex(random_bytes(2)), 'slug' => 'p4cat-' . bin2hex(random_bytes(2))]);
        $this->termIds[] = $category->id;

        $published = $this->makePage(['title' => 'CatPublished']);
        $this->makePage(['title' => 'CatDraft', 'status' => 'draft']);

        $this->attachTerm('page_categories', 'page_categories', 'category_id', $category->id, $published->id);
        $draft = $this->makePage(['title' => 'CatDraft2', 'status' => 'draft']);
        $this->attachTerm('page_categories', 'page_categories', 'category_id', $category->id, $draft->id);

        $html = $this->runRoute('GET', '/category/' . $category->slug);

        $this->assertStringContainsString($category->name, $html);
        $this->assertStringContainsString('CatPublished', $html);
        $this->assertStringNotContainsString('CatDraft', $html, 'drafts must never appear in archives');
    }

    public function test_tag_archive_lists_visible_pages()
    {
        $tag = Tag::create(['name' => 'P4Tag ' . bin2hex(random_bytes(2)), 'slug' => 'p4tag-' . bin2hex(random_bytes(2))]);
        $this->termIds[] = $tag->id;

        $published = $this->makePage(['title' => 'TaggedPost']);
        $this->attachTerm('page_tags', 'page_tags', 'tag_id', $tag->id, $published->id);

        $html = $this->runRoute('GET', '/tag/' . $tag->slug);

        $this->assertStringContainsString('TaggedPost', $html);
    }

    public function test_unknown_archive_term_renders_themed_404()
    {
        $html = $this->runRoute('GET', '/category/does-not-exist-' . bin2hex(random_bytes(3)));

        $this->assertSame(404, \Flight::response()->status());
        $this->assertStringContainsString('404', $html);
        $this->assertStringContainsString('Search', $html, 'the themed 404 view has a search box');
    }

    /* ------------------------------------------------------------- RSS */

    public function test_feed_xml_lists_the_latest_visible_posts_only()
    {
        $old = $this->makePage(['title' => 'FeedOld', 'published_at' => \Illuminate\Support\Carbon::now()->subDays(5)]);
        $new = $this->makePage(['title' => 'FeedNew', 'published_at' => \Illuminate\Support\Carbon::now()->subDay()]);
        $this->makePage(['title' => 'FeedDraft', 'status' => 'draft']);

        ob_start();
        try {
            \App\Controllers\Site\SiteController::feed();
        } finally {
            $xml = (string) ob_get_clean();
        }

        $this->assertStringContainsString('<rss version="2.0"', $xml);
        $this->assertStringContainsString('FeedNew', $xml);
        $this->assertStringContainsString('FeedOld', $xml);
        $this->assertStringNotContainsString('FeedDraft', $xml);
        $this->assertStringContainsString('<link>' . url('/' . $new->slug) . '</link>', $xml);
    }

    /* ------------------------------------------------- JSON-LD / og:type */

    public function test_seo_partial_emits_article_og_type_and_json_ld_for_posts()
    {
        $page = $this->makePage(['title' => 'SEO Post', 'featured_image' => '/uploads/media/seo.png']);
        $page->type = 'post';
        $page->updated_at = \Illuminate\Support\Carbon::now();

        $html = $this->render('partials.seo', ['page' => $page, 'breadcrumbs' => []]);

        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('headline', $html);
    }

    public function test_seo_partial_emits_website_og_type_for_plain_pages()
    {
        $page = $this->makePage(['title' => 'Plain Page', 'type' => 'page']);

        $html = $this->render('partials.seo', ['page' => $page, 'breadcrumbs' => []]);

        $this->assertStringContainsString('property="og:type" content="website"', $html);
        $this->assertStringNotContainsString('"@type":"Article"', $html);
    }

    /* ---------------------------------------------------- redirects */

    private function makeRedirect(string $from, string $to, int $type = 301): int
    {
        DB::table('redirects')->insert([
            'old_url' => $from,
            'new_url' => $to,
            'type' => $type,
        ]);
        $id = (int) DB::getPdo()->lastInsertId();
        $this->redirectIds[] = $id;
        return $id;
    }

    public function test_redirect_match_returns_target_and_status()
    {
        $this->makeRedirect('/old-page', '/new-page', 301);

        $match = RedirectController::matchFor('/old-page');

        $this->assertNotNull($match);
        $this->assertSame(['/new-page', 301], $match);
    }

    public function test_redirect_match_normalizes_trailing_slash_and_is_302_capable()
    {
        $this->makeRedirect('/promo', 'https://external.example/landing', 302);

        $match = RedirectController::matchFor('/promo/');
        $this->assertSame(['https://external.example/landing', 302], $match);

        $this->assertNull(RedirectController::matchFor('/no-such-redirect'), 'unknown paths must not redirect');
    }

    public function test_redirect_controller_crud_persists_and_normalizes()
    {
        $request = new \flight\net\Request();
        $request->url = '/admin/redirects';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection([
            'old_url' => '/old-page',
            'new_url' => '/new-page',
            'type' => '302',
        ]);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        (new RedirectController())->store();

        $row = DB::table('redirects')->where('old_url', '/old-page')->first();
        $this->assertNotNull($row, 'store must persist the redirect');
        $this->redirectIds[] = (int) $row->id;
        $this->assertSame('/new-page', $row->new_url);
        $this->assertSame(302, (int) $row->type);

        // idempotent re-store updates rather than duplicating
        (new RedirectController())->store();
        $this->assertSame(1, DB::table('redirects')->where('old_url', '/old-page')->count());
    }

    public function test_redirect_controller_destroy_removes_the_row()
    {
        $id = $this->makeRedirect('/delete-me', '/target');

        $request = new \flight\net\Request();
        $request->url = '/admin/redirects';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection([]);
        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        (new RedirectController())->destroy($id);

        $this->assertNull(DB::table('redirects')->where('id', $id)->first());
    }
}
