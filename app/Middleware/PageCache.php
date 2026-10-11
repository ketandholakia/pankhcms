<?php

namespace App\Middleware;

/**
 * Full-page cache for anonymous GET traffic.
 *
 * Only 200 responses without CSRF tokens or captcha fields are stored.
 * The cache key is the base-path-aware request path plus whitelisted
 * query parameters; requests with any other query string are not cached
 * at all. Cleared via PageCache::clear() from content change events.
 */
class PageCache
{
    protected static $cacheDir;
    protected static $cacheFile;
    protected static $cacheEnabled = true;

    /** Query parameters that may vary between cached variants of a path. */
    private const CACHEABLE_QUERY_PARAMS = ['page'];

    /** Maximum number of cached pages kept on disk. */
    private const MAX_CACHE_FILES = 500;

    /** Seconds a cached page stays fresh. */
    private const TTL = 3600;

    public static function before()
    {
        self::$cacheFile = null;

        if (!self::shouldCache()) {
            return true;
        }

        $params = self::whitelistedQueryParams();
        if ($params === null) {
            // Unknown query string — content may vary; don't cache.
            return true;
        }

        self::$cacheDir = dirname(__DIR__, 2) . '/storage/cache/pages';
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }

        self::$cacheFile = self::$cacheDir . '/' . md5(self::requestPath() . '?' . http_build_query($params)) . '.html';

        if (is_file(self::$cacheFile) && (time() - filemtime(self::$cacheFile) < self::TTL)) {
            header('X-Pankh-Cache: HIT');
            header('Content-Type: text/html; charset=utf-8');
            readfile(self::$cacheFile);
            exit;
        }

        // Capture the final body just before it is sent (Flight v3).
        \Flight::response()->addResponseBodyCallback([self::class, 'captureResponse']);

        return true;
    }

    /**
     * Response body callback: stores the finished 200 response.
     */
    public static function captureResponse(string $body): string
    {
        if (self::$cacheFile === null) {
            return $body;
        }
        // Guard against a response being sent twice.
        $cacheFile = self::$cacheFile;
        self::$cacheFile = null;

        if ($body === '' || \Flight::response()->status() !== 200) {
            return $body;
        }

        if (self::contentHasDynamicMarkers($body)) {
            return $body;
        }

        @file_put_contents($cacheFile, $body, LOCK_EX);
        self::enforceCacheLimit();

        return $body;
    }

    /**
     * Legacy hook: response capturing happens in captureResponse() now.
     */
    public static function after()
    {
        // no-op
    }

    public static function clear()
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/pages';
        if (is_dir($dir)) {
            $files = glob($dir . '/*.html') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    protected static function shouldCache()
    {
        if (!self::$cacheEnabled) return false;

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'GET' && $method !== 'HEAD') return false;

        $path = self::requestPath();
        foreach (['/admin', '/install', '/api', '/contact', '/themes/'] as $prefix) {
            if (str_starts_with($path, $prefix)) return false;
        }

        // Disable cache if logged in
        if (\App\Core\Auth::check()) return false;

        return true;
    }

    /**
     * The request path relative to the app base path (APP_URL may point
     * at a subdirectory such as /PankhCMS/public).
     */
    protected static function requestPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $basePath = rtrim((string) (parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?? ''), '/');
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
            if ($path === '') {
                $path = '/';
            }
        }

        return $path;
    }

    /**
     * Returns the cache-key-relevant query parameters, or null when the
     * request carries parameters outside the whitelist.
     */
    private static function whitelistedQueryParams(): ?array
    {
        $out = [];
        foreach ($_GET as $name => $value) {
            if (!in_array((string) $name, self::CACHEABLE_QUERY_PARAMS, true)) {
                return null;
            }
            if ((string) $value !== '') {
                $out[$name] = (string) $value;
            }
        }
        ksort($out);
        return $out;
    }

    private static function contentHasDynamicMarkers(string $content): bool
    {
        return str_contains($content, 'name="_csrf"')
            || str_contains($content, "name='_csrf'")
            || str_contains($content, 'name="captcha"');
    }

    private static function enforceCacheLimit(): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/pages';
        $files = glob($dir . '/*.html') ?: [];
        if (count($files) <= self::MAX_CACHE_FILES) {
            return;
        }

        usort($files, fn ($a, $b) => filemtime($a) <=> filemtime($b));
        $excess = count($files) - self::MAX_CACHE_FILES;
        for ($i = 0; $i < $excess; $i++) {
            @unlink($files[$i]);
        }
    }
}
