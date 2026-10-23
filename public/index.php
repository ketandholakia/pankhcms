<?php
$root = dirname(__DIR__);

// Redirect to the installer whenever the application is not configured.
// (A stale install/lock without .env still lands on the installer, which
// refuses to run while it is locked — consistent with Bootstrap and
// the installer itself.)
if (!file_exists($root . '/.env')) {
    header('Location: /install');
    exit;
}

require $root . '/vendor/autoload.php';

// Load .env only (no DB, no plugins, no Blade) so anonymous page-cache
// hits below can resolve APP_URL for base-path detection.
\App\Core\Bootstrap::loadEnv();

// Anonymous GETs without a session cookie are served straight from the
// page cache before the application bootstraps. Maintenance mode stays
// authoritative: the cache is emptied whenever maintenance is toggled
// (SettingsController and BackupManager both call PageCache::clear()).
\App\Middleware\PageCache::serveEarlyIfPossible();

// Lightweight view beacon (POST /_v): records a page view DB-only,
// before the framework boots, so cached pages still count views. The
// handler follows the PHPUNIT_TEST no-exit convention; in production
// it exits right after answering.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') === '/_v') {
    \App\Core\VisitTracker::handleBeacon();
    exit;
}

\App\Core\Bootstrap::init();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAdminPath = str_starts_with($requestPath, '/admin');
$isInstallPath = str_starts_with($requestPath, '/install');
$isStaticAssetPath = str_starts_with($requestPath, '/assets/')
    || str_starts_with($requestPath, '/themes/')
    || str_starts_with($requestPath, '/uploads/')
    || $requestPath === '/favicon.ico'
    || $requestPath === '/robots.txt'
    || $requestPath === '/sitemap.xml';

// Only start a session when it is actually needed: an existing session
// cookie, an admin request, or the contact form (captcha). Anonymous
// visitors keep session-less responses so full-page caching works.
$needsSession = $isAdminPath
    || isset($_COOKIE[session_name()])
    || ($requestPath === '/contact' && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');

if ($needsSession) {
    session_init();
}

if (!$isAdminPath && !$isInstallPath && !$isStaticAssetPath) {
    $maintenanceMode = false;
    $maintenanceMessage = 'We are upgrading our website. Please check back soon.';
    $maintenanceAllowedIps = ['127.0.0.1', '::1'];

    // One query for the whole settings table (cached per request).
    $settings = \settings_all();
    $maintenanceMode = (string) ($settings['maintenance_mode'] ?? '0') === '1';
    $maintenanceMessage = (string) ($settings['maintenance_message'] ?: $maintenanceMessage);
    $allowedIpsRaw = (string) ($settings['maintenance_allowed_ips'] ?? '');
    if ($allowedIpsRaw !== '') {
        $parsedIps = array_filter(array_map('trim', explode(',', $allowedIpsRaw)), static function ($ip) {
            return $ip !== '';
        });
        $maintenanceAllowedIps = array_values(array_unique(array_merge($maintenanceAllowedIps, $parsedIps)));
    }

    $clientIp = client_ip();
    $isBypassedIp = in_array($clientIp, $maintenanceAllowedIps, true);

    if ($maintenanceMode && !$isBypassedIp) {
        http_response_code(503);
        header('Retry-After: 3600');
        echo \Flight::get('blade')->render('site.maintenance', ['message' => $maintenanceMessage]);
        exit;
    }
}

// Security headers on every response. The early page-cache path sends
// the same headers from PageCache::serveEarlyIfPossible() via the same
// App\Core\SecurityHeaders helper, so cached responses match.
\Flight::before('start', function () {
    \App\Core\SecurityHeaders::send();

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (str_contains($path, '/admin')) {
        // Conservative CSP for the admin panel. 'unsafe-inline' is needed
        // by the bundled TinyMCE editor and the inline admin views; site
        // pages are left alone because themes embed third-party assets.
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
    }
});

// Exceptions: log to storage/logs and show a generic 500 page. Full
// details are printed only when config/app.php debug (APP_DEBUG) is true.
\Flight::map('error', function (Throwable $e) {
    $logDir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $entry = sprintf(
        "[%s] %s: %s in %s:%d\n%s\n\n",
        date('c'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents($logDir . '/error-' . date('Y-m-d') . '.log', $entry, FILE_APPEND | LOCK_EX);

    $config = \Flight::get('config') ?? [];
    $debug = !empty($config['debug']);

    try {
        \Flight::response()
            ->cache(0)
            ->clearBody()
            ->status(500)
            ->write($debug
                ? '<h1>500 — Server error</h1><pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>'
                : '<!DOCTYPE html><html><head><title>Server error</title></head>'
                    . '<body style="font-family:sans-serif;text-align:center;padding-top:80px">'
                    . '<h1>Something went wrong</h1>'
                    . '<p>The server encountered an unexpected error. Please try again later.</p>'
                    . '</body></html>')
            ->send();
    } catch (\Throwable $t) {
        http_response_code(500);
        echo $debug ? (string) $e : 'Internal server error.';
    }
    exit;
});

\Flight::before('start', function() { \App\Middleware\PageCache::before(); });
\Flight::start();
