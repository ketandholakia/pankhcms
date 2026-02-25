<?php
$root = dirname(__DIR__);

if (!file_exists($root . '/.env') && !file_exists(__DIR__ . '/install/lock')) {
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

\Flight::start();
