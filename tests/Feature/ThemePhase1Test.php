<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Core\Theme;
use App\Models\GlobalBlock;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Phase 1 theme/bugfix behaviors: blog index + post rendering, dual-shape
 * global-block rendering with sanitization, the url() subdirectory helper,
 * breadcrumb/menu partial contracts, Theme::meta caching, and the header
 * logo fallback.
 */
class ThemePhase1Test extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];
    private array $blockIds = [];
    private array $menuIds = [];
    private array $itemIds = [];
    private array $mediaIds = [];
    private static bool $booted = false;

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
        self::$booted = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200)->clearBody();
        error_log('T1-START ' . $this->name());
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) { Page::find($id)?->delete(); }
        foreach ($this->blockIds as $id) { GlobalBlock::find($id)?->delete(); }
        foreach ($this->itemIds as $id) { DB::table('menu_items')->where('id', $id)->delete(); }
        foreach ($this->menuIds as $id) { DB::table('menus')->where('id', $id)->delete(); }
        foreach ($this->mediaIds as $id) { Media::find($id)?->delete(); }
        $this->pageIds = []; $this->blockIds = []; $this->menuIds = [];
        $this->itemIds = []; $this->mediaIds = [];
        putenv('APP_URL');
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    /* ------------------------------------------------------- helpers */

    private function render(string $view, array $data): string
    {
        return (string) \Flight::get('blade')->render($view, $data);
    }

    private function makePage(array $data): Page
    {
        $page = Page::create(array_merge([
            'title' => 'Page ' . bin2hex(random_bytes(2)),
            'slug' => 'page-' . bin2hex(random_bytes(3)),
            'status' => 'published',
            'type' => 'page',
        ], $data));
        $this->pageIds[] = $page->id;
        return $page;
    }

    private function makePost(int $daysAgo, string $marker = ''): Page
    {
        return $this->makePage([
            'title' => 'Post ' . $daysAgo . ' ' . $marker,
            'slug' => 'post-' . $daysAgo . '-' . bin2hex(random_bytes(2)),
            'type' => 'post',
            'published_at' => \Illuminate\Support\Carbon::now()->subDays($daysAgo),
        ]);
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

        // Flight's Router::route() CONSUMES routes from a persistent
        // index: after earlier tests' route lookups (index > 0) matching
        // continues mid-list and can land on the /@slug catch-all whose
        // 404 halt exits the process. rewind() resets that state.
        \Flight::router()->rewind();

        $route = \Flight::router()->route($request);
        $this->assertNotFalse($route, "route $url must be registered");

        ob_start();
        try {
            $params = $route->params;
            call_user_func_array($route->callback, array_values($params));
        } finally {
            return (string) ob_get_clean();
        }
    }

    /* ----------------------------------------------------------- blog */

    public function test_blog_route_lists_visible_posts_newest_first_with_pagination()
    {
        // 12 posts + 1 draft + 1 future-scheduled + 1 plain page.
        for ($i = 1; $i <= 12; $i++) { $this->makePost($i); }
        $this->makePage(['title' => 'DraftPost', 'type' => 'post', 'status' => 'draft']);
        $this->makePage(['title' => 'FuturePost', 'type' => 'post', 'published_at' => \Illuminate\Support\Carbon::now()->addDay()]);
        $this->makePage(['title' => 'PlainPage']);

        $_GET = ['page' => '1'];
        $html = $this->runRoute('GET', '/blog');

        $this->assertStringContainsString('Blog', $html);
        // Exactly 10 of the 12 posts on page 1 (two links per card).
        $this->assertSame(10, substr_count($html, 'card post-card'), '10 post cards expected');
        $this->assertStringContainsString('Page 1 of 2', $html);
        $this->assertStringNotContainsString('DraftPost', $html);
        $this->assertStringNotContainsString('FuturePost', $html);
        $this->assertStringNotContainsString('>PlainPage<', $html);
        // Newest first: day-1 post present, day-12 post pushed to page 2.
        $this->assertStringContainsString('Post 1 ', $html);
        $this->assertStringNotContainsString('Post 11 ', $html);

        // Page 2 holds the remaining posts.
        $_GET = ['page' => '2'];
        $html2 = $this->runRoute('GET', '/blog');
        $this->assertStringContainsString('Post 11 ', $html2);
        $this->assertStringContainsString('Page 2 of 2', $html2);
        $_GET = [];
    }

    public function test_blog_is_a_reserved_page_slug()
    {
        $this->assertContains('blog', ['blog'], 'sanity');
        $controller = new \App\Controllers\Admin\PageController();
        $method = new \ReflectionMethod($controller, 'getValidatedInput');
        $method->setAccessible(true);

        $request = new \flight\net\Request();
        $request->url = '/admin/pages';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection(['title' => 'Blog page', 'slug' => 'blog']);
        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        $result = $method->invoke($controller, false);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('reserved path', implode("\n", $result['errors']));
    }

    /* ------------------------------------------------- post rendering */

    public function test_post_template_renders_page_data_sanitized()
    {
        $page = $this->makePage([
            'title' => 'Hello <b>Post</b> World',
            'type' => 'post',
            'featured_image' => '/uploads/media/cover.png',
            'content' => '<p>Body text</p><script>alert(1)</script>',
            'published_at' => \Illuminate\Support\Carbon::now()->subDay(),
        ]);

        $html = $this->render('site.post', [
            'page' => $page,
            'breadcrumbs' => [],
            'blocks' => [],
            'site_name' => 'Test',
            'sliders' => collect(),
        ]);

        $this->assertStringContainsString('Hello &lt;b&gt;Post&lt;/b&gt; World', $html, 'title must be escaped');
        $this->assertStringContainsString('/uploads/media/cover.png', $html, 'featured image must render');
        $this->assertStringContainsString('<p>Body text</p>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html, 'post content must be sanitized');
        $this->assertStringContainsString('time datetime=', $html, 'published date must render');
    }

    /* ------------------------------------------- dual-shape block rendering */

    private function makeBlock(string $type, $content, string $section = 'footer'): GlobalBlock
    {
        $suffix = bin2hex(random_bytes(2));
        $block = GlobalBlock::create([
            'name' => 'blk-' . $suffix,
            'slug' => 'blk-' . $suffix,
            'title' => 'Block ' . $suffix,
            'type' => $type,
            'content' => $content,
        ]);
        $this->blockIds[] = $block->id;

        DB::table('block_placements')->insert([
            'block_id' => $block->id,
            'section' => $section,
            'order' => 0,
        ]);

        return $block;
    }

    public function test_string_block_content_is_sanitized_and_rendered()
    {
        $this->makeBlock('custom', '<p>Safe text</p><script>alert(1)</script>');

        $html = blocks_html('footer');

        $this->assertStringContainsString('Safe text', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html, 'string block content must be sanitized');
        $this->assertStringContainsString('block-custom', $html, 'the theme block template must render');
    }

    public function test_array_block_content_is_sanitized_and_rendered()
    {
        $this->makeBlock('custom', json_encode([
            'data' => ['title' => 'Array Block', 'html' => '<p>Array text</p><script>alert(2)</script>'],
        ]));

        $html = blocks_html('footer');

        $this->assertStringContainsString('Array Block', $html);
        $this->assertStringContainsString('Array text', $html);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $html, 'array block content must be sanitized');
    }

    public function test_cta_block_renders_both_shapes()
    {
        $this->makeBlock('cta', json_encode([
            'data' => ['heading' => 'CTA Heading', 'text' => 'CTA body', 'button_text' => 'Go', 'button_link' => '/contact-us'],
        ]));
        $html = blocks_html('footer');
        $this->assertStringContainsString('CTA Heading', $html);
        $this->assertStringContainsString('CTA body', $html);
        $this->assertStringContainsString('href="' . url('/contact-us') . '"', $html);
        $this->assertStringContainsString('>Go</a>', $html);
    }

    /* --------------------------------------------------- url() helper */

    public function test_url_helper_prefixes_subdirectory_base_path()
    {
        // env() reads $_ENV before getenv() — set both so no earlier
        // test's environment can shadow this test's values.
        $_ENV['APP_URL'] = null;
        putenv('APP_URL');
        $this->assertSame('/blog', url('/blog'), 'no APP_URL: no prefix');
        $this->assertSame('/', url('/'));
        $this->assertSame('/', url(''));

        putenv('APP_URL=http://example.test/cms/public');
        unset($_ENV['APP_URL']);
        $this->assertSame('/cms/public/blog', url('/blog'));
        $this->assertSame('/cms/public/', url('/'));
        $this->assertSame('https://elsewhere.test/x', url('https://elsewhere.test/x'), 'absolute URLs pass through');

        putenv('APP_URL');
        unset($_ENV['APP_URL']);
    }

    /* ----------------------------------------------- breadcrumbs partial */

    public function test_breadcrumbs_render_real_array_with_aria_current_on_last()
    {
        $html = $this->render('partials.breadcrumbs', [
            'breadcrumbs' => [
                ['title' => 'Home', 'url' => '/'],
                ['title' => 'Section', 'url' => '/section'],
                ['title' => 'Current Page', 'url' => '/section/current'],
            ],
            'page' => null,
        ]);

        $this->assertSame(1, substr_count($html, 'aria-current="page"'), 'aria-current on the last item only');
        $this->assertStringContainsString('Section', $html);
        $this->assertStringContainsString('Current Page', $html);
        $this->assertStringContainsString('href="' . url('/') . '"', $html);
    }

    public function test_breadcrumbs_fallback_renders_home_and_title()
    {
        $page = $this->makePage(['title' => 'Orphan Page']);
        $html = $this->render('partials.breadcrumbs', ['breadcrumbs' => [], 'page' => $page]);

        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('Orphan Page', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    /* ------------------------------------------------ menu eager-loading */

    public function test_menu_renders_page_links_without_per_item_queries()
    {
        $target = $this->makePage(['title' => 'Menu Target']);
        $menu = Menu::create(['name' => 'm-' . bin2hex(random_bytes(2)), 'location' => 'menu-test-' . bin2hex(random_bytes(2))]);
        $this->menuIds[] = $menu->id;

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'page_id' => $target->id,
            'url' => null,
            'title' => 'Go To Target',
            'sort_order' => 0,
        ]);
        $this->itemIds[] = $item->id;

        $connection = DB::connection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();

        $html = menu($menu->location);

        $pageQueries = 0;
        foreach ($connection->getQueryLog() as $q) {
            if (str_contains($q['query'], 'from "pages"') || str_contains($q['query'], 'from `pages`')) {
                $pageQueries++;
            }
        }
        $connection->flushQueryLog();
        $connection->disableQueryLog();

        $this->assertStringContainsString('Go To Target', $html, 'the menu item title must render');
        $this->assertStringContainsString('href="' . url('/' . $target->slug) . '"', $html, 'menu must link the page slug');
        $this->assertLessThanOrEqual(1, $pageQueries, 'menu rendering must use at most ONE pages query (eager-loaded), not one per item');
    }

    /* ------------------------------------------------------- Theme::meta */

    public function test_theme_meta_reads_theme_json_and_is_cached()
    {
        $meta = Theme::meta('pankhcmsstarter');
        $this->assertIsArray($meta);
        $this->assertSame($meta, Theme::meta('pankhcmsstarter'), 'second call must be served from the static cache');
        $this->assertSame([], Theme::meta('theme-that-does-not-exist'), 'missing theme yields an empty array');
    }

    /* ------------------------------------------------- header logo fallback */

    public function test_header_falls_back_to_text_logo_when_logo_file_missing()
    {
        DB::table('settings')->updateOrInsert(['key' => 'logo_path'], ['value' => '/public/assets/tinymce/logo.png']);
        DB::table('settings')->updateOrInsert(['key' => 'logo_enabled'], ['value' => '1']);
        DB::table('settings')->updateOrInsert(['key' => 'site_title'], ['value' => 'Logo Fallback Test']);
        setting_cache_reset();

        try {
            $html = $this->render('partials.header', []);
            $this->assertStringContainsString('Logo Fallback Test', $html, 'text logo must render');
            $this->assertStringNotContainsString('<img', $html, 'no broken logo img may render when the file is missing');
        } finally {
            foreach (['logo_path', 'logo_enabled', 'site_title'] as $key) {
                DB::table('settings')->where('key', $key)->delete();
            }
            setting_cache_reset();
        }
        setting_cache_reset();
    }
}
