<?php

namespace App\Middleware;

/**
 * Full-page cache for anonymous GET traffic.
 *
 * Only 200 responses without CSRF tokens or captcha fields are stored.
 * The cache key is the Host header plus the base-path-aware request path
 * plus whitelisted query parameters; requests with any other query
 * string are not cached at all. Cleared via PageCache::clear() from
 * content change events AND whenever maintenance mode is toggled, so
 * anonymous hits served before bootstrap can never be stale relative to
 * maintenance state.
 */
class PageCache
{
    protected static $cacheDir;
    protected static $cacheFile;
    protected static $cacheEnabled = true;

    /** Query parameters that may vary between cached variants of a path. */
    private const CACHEABLE_QUERY_PARAMS = ['page'];

    /** Path prefixes never cached. */
    private const EXCLUDED_PREFIXES = ['/admin', '/install', '/api', '/contact', '/themes/'];

    /** Maximum number of cached pages kept on disk. */
    private const MAX_CACHE_FILES = 500;

    /** Seconds a cached page stays fresh. */
    private const TTL = 3600;

    /**
     * Serve a cache hit for anonymous GET/HEAD traffic without session
     * cookie before the application bootstraps (no DB, no plugins, no
     * Blade). Called from public/index.php right after the environment
     * is loaded. Maintenance mode is authoritative because the whole
     * page cache is emptied on every maintenance toggle.
     */
    public static function serveEarlyIfPossible(): void
    {
        if (!self::$cacheEnabled) {
            return;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }

        // A session cookie means the visitor may be authenticated; leave
        // those requests to the normal (DB-aware) flow.
        if (isset($_COOKIE[session_name()])) {
            return;
        }

        $path = self::requestPath();
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $params = self::whitelistedQueryParams();
        if ($params === null) {
            return;
        }

        // Unknown/forged Host: never serve from cache.
        if (self::cacheHost() === null) {
            return;
        }

        $file = self::cacheDir() . '/' . self::cacheKey($path, $params) . '.html';
        if (!is_file($file) || (time() - filemtime($file) >= self::TTL)) {
            return;
        }

        // Early hits bypass public/index.php's Flight::before('start')
        // hook, so the site-wide security headers must be sent here too.
        \App\Core\SecurityHeaders::send();

        header('X-Pankh-Cache: HIT');
        header('Content-Type: text/html; charset=utf-8');
        readfile($file);

        // The response is out; count the view now (DB-only, best effort).
        self::trackEarlyHit($path);
        exit;
    }

    /**
     * Count a view served from the early cache. Chosen over a tracking
     * beacon: the HTML response itself stays completely session-less and
     * DB-free, no JavaScript is injected, and no second request runs a
     * full bootstrap. The visitor has no session cookie on this path (a
     * session cookie skips the early cache entirely), so VisitTracker
     * stores no session id and the response never starts a session.
     * Browsers-only: known bots are skipped so statistics are not
     * flooded. Runs AFTER the cached body has been sent, DB-only (no
     * plugins, no Blade), and every failure is swallowed — analytics
     * must never break a cache hit.
     */
    public static function trackEarlyHit(string $path): void
    {
        $userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (self::isBot($userAgent)) {
            return;
        }

        try {
            // DB-only bootstrap; require_once is a no-op when the app is
            // already bootstrapped (tests, normal-flow fallbacks).
            require_once dirname(__DIR__, 2) . '/app/database.php';

            $slug = ltrim($path, '/');
            $pageId = \Illuminate\Database\Capsule\Manager::table('pages')
                ->where('slug', $slug)
                ->value('id');

            \App\Core\VisitTracker::track($pageId !== null ? (int) $pageId : null, $path);
        } catch (\Throwable $e) {
            // Best effort only.
        }
    }

    /**
     * Conservative crawler detector for view counting (empty agents are
     * treated as bots: no real browser sends no User-Agent).
     */
    public static function isBot(string $userAgentLower): bool
    {
        if (trim($userAgentLower) === '') {
            return true;
        }
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|curl|wget|facebookexternalhit|headless|lighthouse|monitor|pingdom|uptime/',
            $userAgentLower
        );
    }

    /**
     * The cache file that would serve the current request in the early
     * (pre-bootstrap) path, or null when that path does not apply.
     * Exposed for tests; contains no side effects and touches no DB.
     */
    public static function earlyCacheFile(): ?string
    {
        if (!self::$cacheEnabled) {
            return null;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'GET' && $method !== 'HEAD') {
            return null;
        }

        if (isset($_COOKIE[session_name()])) {
            return null;
        }

        $path = self::requestPath();
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return null;
            }
        }

        $params = self::whitelistedQueryParams();
        if ($params === null) {
            return null;
        }

        if (self::cacheHost() === null) {
            return null;
        }

        $file = self::cacheDir() . '/' . self::cacheKey($path, $params) . '.html';
        return (is_file($file) && (time() - filemtime($file) < self::TTL)) ? $file : null;
    }

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

        self::$cacheFile = self::cacheDir() . '/' . self::cacheKey(self::requestPath(), $params) . '.html';

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
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) return false;
        }

        // Unknown/forged Host: never create cache files.
        if (self::cacheHost() === null) return false;

        // Disable cache if logged in
        if (\App\Core\Auth::check()) return false;

        return true;
    }

    private static function cacheDir(): string
    {
        if (self::$cacheDir === null) {
            self::$cacheDir = dirname(__DIR__, 2) . '/storage/cache/pages';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }

    /**
     * Cache key: allowlisted host + base-path-aware path + whitelisted
     * query params. The host is part of the key (and must pass the
     * ALLOWED_HOSTS/APP_URL allowlist) so two sites can never be served
     * each other's cached pages and a forged Host can never poison one.
     */
    private static function cacheKey(string $path, array $params): string
    {
        return md5((self::cacheHost() ?? '') . '|' . $path . '?' . http_build_query($params));
    }

    /**
     * The hostname this request may be cached under: the port-stripped,
     * lowercased Host, but ONLY when it is allowlisted. The allowlist is
     * ALLOWED_HOSTS (comma-separated) plus the host of APP_URL; with
     * neither configured (only possible in tests) any host is accepted,
     * preserving the previous behavior.
     */
    private static function cacheHost(): ?string
    {
        $raw = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($raw === '') {
            return null;
        }
        $host = preg_replace('/:\d+$/', '', $raw) ?: $raw;

        $allowed = self::allowedHosts();
        if ($allowed === []) {
            return $host;
        }

        return in_array($host, $allowed, true) ? $host : null;
    }

    /** @return string[] lowercased hostnames allowed to use the cache */
    private static function allowedHosts(): array
    {
        $hosts = [];

        $raw = trim((string) env('ALLOWED_HOSTS', ''));
        if ($raw !== '') {
            foreach (explode(',', $raw) as $candidate) {
                $candidate = strtolower(trim((string) preg_replace('/:\d+$/', '', $candidate)));
                if ($candidate !== '') {
                    $hosts[] = $candidate;
                }
            }
        }

        $appUrl = trim((string) env('APP_URL', ''));
        if ($appUrl !== '') {
            $fromAppUrl = strtolower((string) (parse_url($appUrl, PHP_URL_HOST) ?? ''));
            if ($fromAppUrl !== '') {
                $hosts[] = $fromAppUrl;
            }
        }

        return array_values(array_unique($hosts));
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
        foreach (($_GET ?? []) as $name => $value) {
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
