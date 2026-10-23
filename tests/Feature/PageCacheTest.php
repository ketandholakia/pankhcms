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
        putenv('ALLOWED_HOSTS');
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

    public function test_settings_all_uses_a_single_query()
    {
        setting_cache_reset();
        $connection = \Illuminate\Database\Capsule\Manager::connection();
        $connection->enableQueryLog();
        $connection->flushQueryLog(); // the log accumulates across tests

        $settings = settings_all();
        $firstCallQueries = count($connection->getQueryLog());

        $connection->flushQueryLog();
        settings_all();
        $secondCallQueries = count($connection->getQueryLog());

        $connection->flushQueryLog();
        $connection->disableQueryLog();

        $this->assertLessThanOrEqual(2, $firstCallQueries, "first settings_all() must run at most hasTable + one pluck query");
        $this->assertSame(0, $secondCallQueries, "repeated settings_all() must be served from the per-request cache");
        $this->assertIsArray($settings);
    }

    public function test_settings_all_survives_missing_settings_table()
    {
        $schema = \Illuminate\Database\Capsule\Manager::schema();
        $schema->drop("settings");
        setting_cache_reset();

        $this->assertSame([], settings_all(), "a missing settings table must yield an empty array, not an error");

        // Restore the table for the other tests.
        $schema->create("settings", function ($t) {
            $t->string("key")->primary();
            $t->text("value")->nullable();
        });
        setting_cache_reset();
    }

    public function test_early_cache_file_hit_uses_host_in_key_and_touches_no_db()
    {
        putenv("ALLOWED_HOSTS=example.test,other.example.test");
        $_SERVER["HTTP_HOST"] = "example.test";
        $_SERVER["REQUEST_URI"] = "/cached-page";

        $connection = \Illuminate\Database\Capsule\Manager::connection();
        $connection->enableQueryLog();
        $connection->flushQueryLog(); // the log accumulates across tests

        // Build the expected file through the same key logic production uses.
        $keyMethod = new \ReflectionMethod(PageCache::class, "cacheKey");
        $keyMethod->setAccessible(true);
        $dirMethod = new \ReflectionMethod(PageCache::class, "cacheDir");
        $dirMethod->setAccessible(true);
        $file = $dirMethod->invoke(null) . "/" . $keyMethod->invoke(null, "/cached-page", []) . ".html";
        file_put_contents($file, "<html>cached body</html>");

        $resolved = PageCache::earlyCacheFile();

        $queries = count($connection->getQueryLog());

        $connection->flushQueryLog();
        \Illuminate\Database\Capsule\Manager::table("settings")->count(); // prove connection works
        $queriesAfterProbe = count($connection->getQueryLog());

        $connection->flushQueryLog();
        $connection->disableQueryLog();

        $this->assertSame($file, $resolved, "early cache must resolve the seeded file");
        $this->assertSame(0, $queries, "earlyCacheFile() must not touch the database");
        $this->assertSame(1, $queriesAfterProbe, "the probe query proves the measurement works");
        $this->assertStringContainsString("cached body", (string) file_get_contents($file));
    }

    public function test_early_cache_misses_on_different_host()
    {
        putenv("ALLOWED_HOSTS=example.test,other.example.test");
        $_SERVER["HTTP_HOST"] = "example.test";
        $_SERVER["REQUEST_URI"] = "/host-page";

        $keyMethod = new \ReflectionMethod(PageCache::class, "cacheKey");
        $keyMethod->setAccessible(true);
        $dirMethod = new \ReflectionMethod(PageCache::class, "cacheDir");
        $dirMethod->setAccessible(true);
        $file = $dirMethod->invoke(null) . "/" . $keyMethod->invoke(null, "/host-page", []) . ".html";
        file_put_contents($file, "<html>for example.test only</html>");

        $_SERVER["HTTP_HOST"] = "other.example.test";
        $this->assertNull(PageCache::earlyCacheFile(), "a different Host must never be served this cache entry");
    }

    public function test_early_cache_skips_session_cookies_and_excluded_paths()
    {
        putenv("ALLOWED_HOSTS=example.test,other.example.test");
        $_SERVER["HTTP_HOST"] = "example.test";

        // Session cookie present -> defer to the normal flow
        $_SERVER["REQUEST_URI"] = "/any";
        $_COOKIE[session_name()] = "sid";
        $this->assertNull(PageCache::earlyCacheFile());
        unset($_COOKIE[session_name()]);

        // Excluded prefixes
        foreach (["/admin", "/install", "/api", "/contact", "/themes/"] as $prefix) {
            $_SERVER["REQUEST_URI"] = $prefix . "more";
            $this->assertNull(PageCache::earlyCacheFile(), "$prefix must not be early-cached");
        }

        // Unknown query strings
        unset($_GET);
        $_GET["search"] = "x";
        $_SERVER["REQUEST_URI"] = "/any?search=x";
        $this->assertNull(PageCache::earlyCacheFile());
    }

    public function test_maintenance_toggle_clears_the_page_cache()
    {
        $dbFile = sys_get_temp_dir() . "/maint_toggle_" . uniqid() . ".sqlite";
        file_put_contents($dbFile, "");
        $manager = new \App\Core\BackupManager(["driver" => "sqlite", "database" => $dbFile]);
        $setMaintenance = new \ReflectionMethod(\App\Core\BackupManager::class, 'setMaintenance');
        $setMaintenance->setAccessible(true);

        $cached = $this->cacheDir . "/stale-during-maintenance.html";
        file_put_contents($cached, "<html>stale</html>");

        $setMaintenance->invoke($manager, true);
        $this->assertFileDoesNotExist($cached, "enabling maintenance must clear the page cache");

        file_put_contents($cached, "<html>stale again</html>");
        $setMaintenance->invoke($manager, false);
        $this->assertFileDoesNotExist($cached, "disabling maintenance must clear the page cache too");

        $this->assertSame("0", (string) \Illuminate\Database\Capsule\Manager::table("settings")
            ->where("key", "maintenance_mode")->value("value"));
        @unlink($dbFile);
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
