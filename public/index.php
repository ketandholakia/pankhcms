<?php

$root = dirname(__DIR__);

if (!file_exists($root . '/.env') && !file_exists(__DIR__ . '/install/lock')) {
    header('Location: /install');
    exit;
}

require $root . '/vendor/autoload.php';

// Ensure helpers are loaded
require_once $root . '/app/Helpers/functions.php';
require_once $root . '/app/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

\App\Core\Bootstrap::init();

\Flight::start();
