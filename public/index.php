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

\App\Core\Bootstrap::init();

session_init();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAdminPath = str_starts_with($requestPath, '/admin');
$isInstallPath = str_starts_with($requestPath, '/install');
$isStaticAssetPath = str_starts_with($requestPath, '/assets/')
    || str_starts_with($requestPath, '/themes/')
    || str_starts_with($requestPath, '/uploads/')
    || $requestPath === '/favicon.ico'
    || $requestPath === '/robots.txt'
    || $requestPath === '/sitemap.xml';

if (!$isAdminPath && !$isInstallPath && !$isStaticAssetPath) {
    $maintenanceMode = false;
    $maintenanceMessage = 'We are upgrading our website. Please check back soon.';
    $maintenanceAllowedIps = ['127.0.0.1', '::1'];

    try {
        if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('settings')) {
            $maintenanceMode = (string) (\Illuminate\Database\Capsule\Manager::table('settings')->where('key', 'maintenance_mode')->value('value') ?? '0') === '1';
            $maintenanceMessage = (string) (\Illuminate\Database\Capsule\Manager::table('settings')->where('key', 'maintenance_message')->value('value') ?: $maintenanceMessage);
            $allowedIpsRaw = (string) (\Illuminate\Database\Capsule\Manager::table('settings')->where('key', 'maintenance_allowed_ips')->value('value') ?? '');
            if ($allowedIpsRaw !== '') {
                $parsedIps = array_filter(array_map('trim', explode(',', $allowedIpsRaw)), static function ($ip) {
                    return $ip !== '';
                });
                $maintenanceAllowedIps = array_values(array_unique(array_merge($maintenanceAllowedIps, $parsedIps)));
            }
        }
    } catch (\Throwable $e) {
        $maintenanceMode = false;
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

// Security headers on every response.
\Flight::before('start', function () {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

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
\Flight::after('start', function() { \App\Middleware\PageCache::after(); });
\Flight::start();
