<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Models\Page;

/**
 * /sitemap.xml is served exclusively by the core route in routes/web.php
 * (the Sitemap plugin that used to shadow it with a weaker,
 * scheduled-page-leaking handler has been removed). The test executes
 * the real registered route callback and asserts scheduled-future pages
 * are absent while published pages are present.
 */
class SitemapRouteTest extends TestCase
{
    private static bool $routesLoaded = false;

    /** @var int[] */
    private array $pageIds = [];

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);

        if (!self::$routesLoaded) {
            $loadRoutes = new \ReflectionMethod(Bootstrap::class, 'loadRoutes');
            $loadRoutes->invoke(null);
            self::$routesLoaded = true;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
        parent::tearDown();
    }

    private function sitemapOutput(): string
    {
        $request = new \flight\net\Request();
        $request->url = '/sitemap.xml';
        $request->method = 'GET';

        $route = \Flight::router()->route($request);
        $this->assertNotFalse($route, 'GET /sitemap.xml must be registered');

        ob_start();
        try {
            call_user_func($route->callback);
        } finally {
            $output = ob_get_clean();
        }
        return (string) $output;
    }

    public function test_scheduled_future_page_is_absent_and_published_page_present()
    {
        $future = Page::create([
            'title' => 'Sitemap Future ' . bin2hex(random_bytes(2)),
            'slug' => 'sitemap-future-' . bin2hex(random_bytes(2)),
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->addDay(),
        ]);
        $live = Page::create([
            'title' => 'Sitemap Live ' . bin2hex(random_bytes(2)),
            'slug' => 'sitemap-live-' . bin2hex(random_bytes(2)),
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->subDay(),
        ]);
        $this->pageIds = [$future->id, $live->id];

        $xml = $this->sitemapOutput();

        $this->assertStringNotContainsString($future->slug, $xml, 'a future-scheduled page must not be in the sitemap');
        $this->assertStringContainsString($live->slug, $xml, 'a published page must be in the sitemap');
    }

    public function test_sitemap_route_is_registered_exactly_once()
    {
        $count = 0;
        foreach (\Flight::router()->getRoutes() as $route) {
            if ($route->pattern === '/sitemap.xml') {
                $count++;
            }
        }
        $this->assertSame(1, $count, 'the plugin must no longer register a duplicate /sitemap.xml route');
    }
}
