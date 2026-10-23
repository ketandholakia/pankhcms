<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Middleware\PageCache;

/**
 * The page cache is keyed by an ALLOWLISTED host (ALLOWED_HOSTS plus the
 * host of APP_URL), not the raw Host header. A request whose Host is not
 * allowlisted must never be served from cache and — critically — must
 * never create cache files, so a forged Host cannot poison the cache.
 */
class PageCacheHostAllowlistTest extends TestCase
{
    private string $cacheDir;
    private array $files = [];
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache/pages';
        $this->serverBackup = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/allowlist-probe';
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        $_COOKIE = [];
        putenv('ALLOWED_HOSTS');
        putenv('APP_URL');
        parent::tearDown();
    }

    private function seedForHost(string $host): string
    {
        $_SERVER['HTTP_HOST'] = $host;
        $keyMethod = new \ReflectionMethod(PageCache::class, 'cacheKey');
        $keyMethod->setAccessible(true);
        $file = $this->cacheDir . '/' . $keyMethod->invoke(null, '/allowlist-probe', []) . '.html';
        file_put_contents($file, '<html>allowlist</html>');
        $this->files[] = $file;
        return $file;
    }

    public function test_allowlisted_host_is_served_from_the_early_cache()
    {
        putenv('ALLOWED_HOSTS=allowed.example');
        $this->seedForHost('allowed.example');

        $this->assertNotNull(PageCache::earlyCacheFile(), 'an allowlisted host must be cacheable');
    }

    public function test_port_suffix_is_ignored_for_matching()
    {
        putenv('ALLOWED_HOSTS=allowed.example');
        $this->seedForHost('allowed.example:8080');

        $this->assertNotNull(PageCache::earlyCacheFile(), 'same host on another port must hit the same entry');
    }

    public function test_unknown_host_is_not_served_and_cannot_seed_the_cache()
    {
        putenv('ALLOWED_HOSTS=allowed.example');

        // A file seeded for the unknown host under the OLD raw-host key
        // must not be found: unknown hosts are uncacheable, full stop.
        $_SERVER['HTTP_HOST'] = 'evil.example';
        $legacyKey = md5('evil.example|/allowlist-probe?' . http_build_query([]));
        $legacyFile = $this->cacheDir . '/' . $legacyKey . '.html';
        file_put_contents($legacyFile, '<html>poison</html>');
        $this->files[] = $legacyFile;

        $this->assertNull(PageCache::earlyCacheFile(), 'an unknown Host must never be served from cache');

        // And the write path must refuse it too.
        $shouldCache = new \ReflectionMethod(PageCache::class, 'shouldCache');
        $shouldCache->setAccessible(true);
        \App\Core\Auth::logout();
        $this->assertFalse($shouldCache->invoke(null), 'an unknown Host must never create cache files');
    }

    public function test_app_url_host_extends_the_allowlist()
    {
        putenv('ALLOWED_HOSTS=');
        putenv('APP_URL=http://from-app-url.example/subdir');
        $this->seedForHost('from-app-url.example');

        $this->assertNotNull(PageCache::earlyCacheFile(), 'the APP_URL host must be cacheable');

        $_SERVER['HTTP_HOST'] = 'not-in-app-url.example';
        $this->assertNull(PageCache::earlyCacheFile(), 'hosts outside APP_URL/ALLOWED_HOSTS must not be served');
    }

    public function test_empty_configuration_keeps_legacy_permissive_behavior()
    {
        // Tests and unconfigured environments only: with neither
        // ALLOWED_HOSTS nor APP_URL set, any host stays cacheable.
        putenv('ALLOWED_HOSTS=');
        putenv('APP_URL=');
        $this->seedForHost('anything.example');

        $this->assertNotNull(PageCache::earlyCacheFile());
    }
}
