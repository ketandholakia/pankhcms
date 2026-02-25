<?php
require_once __DIR__ . '/../vendor/autoload.php';
\App\Core\Bootstrap::init();

// Ensure session functions used in public/index.php exist
if (!function_exists('session_init')) {
    function session_init() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
}

// Call the SiteController::home and capture output
ob_start();
\App\Controllers\Site\SiteController::home();
$html = ob_get_clean();

echo "----BEGIN-HOMEPAGE-HTML----\n";
echo $html ?: '(empty)';
echo "\n----END-HOMEPAGE-HTML----\n";
