<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Middleware\PageCache;

class PageCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache/pages';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        PageCache::clear();
        unset($_GET);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/some-page';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        putenv('APP_URL=');
        $_ENV['APP_URL'] = '';
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
    }

    protected function tearDown(): void
    {
        PageCache::clear();
        putenv('APP_URL');
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function setCacheFileViaReflection(string $file): void
    {
        $prop = new \ReflectionProperty(PageCache::class, 'cacheFile');
        $prop->setAccessible(true);
        $prop->setValue(null, $file);
    }

    public function test_capture_writes_clean_200_response()
    {
        $file = $this->cacheDir . '/clean.html';
        $this->setCacheFileViaReflection($file);

        \Flight::response()->status(200);
        $out = PageCache::captureResponse('<html><body>Fresh page</body></html>');

        $this->assertSame('<html><body>Fresh page</body></html>', $out);
        $this->assertFileExists($file);
        $this->assertStringContainsString('Fresh page', (string) file_get_contents($file));
    }

    public function test_capture_skips_csrf_and_captcha_pages()
    {
        $file = $this->cacheDir . '/csrf.html';
        $this->setCacheFileViaReflection($file);
        \Flight::response()->status(200);

        PageCache::captureResponse('<form><input type="hidden" name="_csrf" value="x"></form>');
        $this->assertFileDoesNotExist($file);

        PageCache::captureResponse('<form><input name="captcha" value="x"></form>');
        $this->assertFileDoesNotExist($file);
    }

    public function test_capture_skips_non_200_responses()
    {
        $file = $this->cacheDir . '/missing.html';
        $this->setCacheFileViaReflection($file);
        \Flight::response()->status(404);

        PageCache::captureResponse('<html>404 page</html>');
        $this->assertFileDoesNotExist($file);
    }

    public function test_clear_removes_cached_files()
    {
        $file = $this->cacheDir . '/stale.html';
        file_put_contents($file, '<html>x</html>');

        PageCache::clear();

        $this->assertFileDoesNotExist($file);
    }

    public function test_request_path_strips_app_url_base()
    {
        $_ENV['APP_URL'] = 'http://localhost/PankhCMS/public';
        putenv('APP_URL=http://localhost/PankhCMS/public');
        $_SERVER['REQUEST_URI'] = '/PankhCMS/public/admin/pages';

        $method = new \ReflectionMethod(PageCache::class, 'requestPath');
        $method->setAccessible(true);

        $this->assertSame('/admin/pages', $method->invoke(null));
    }

    public function test_page_published_event_clears_page_cache()
    {
        // Register the production listeners exactly as Bootstrap does
        // (loadRoutes is private).
        $method = new \ReflectionMethod(\App\Core\Bootstrap::class, 'loadRoutes');
        $method->setAccessible(true);
        $method->invoke(null);

        $file = $this->cacheDir . '/published-page.html';
        file_put_contents($file, '<html>x</html>');
        $this->assertFileExists($file);

        \Event::dispatch('page.published', new \App\Models\Page());

        $this->assertFileDoesNotExist($file, 'page.published must invalidate the page cache');
    }

    public function test_whitelisted_query_params()
    {
        $method = new \ReflectionMethod(PageCache::class, 'whitelistedQueryParams');
        $method->setAccessible(true);

        $_GET = [];
        $this->assertSame([], $method->invoke(null));

        $_GET = ['page' => '3'];
        $this->assertSame(['page' => '3'], $method->invoke(null));

        // Unknown params make the request uncacheable
        $_GET = ['search' => 'hello'];
        $this->assertNull($method->invoke(null));
    }
}
