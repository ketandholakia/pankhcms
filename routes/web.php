<?php

// Public Contact Form Routes
Flight::route('GET /contact', ['App\Controllers\ContactController', 'show']);
Flight::route('POST /contact', ['App\Controllers\ContactController', 'submit']);

// Theme assets (supports themes stored outside public/)
Flight::route('GET /themes/@theme/assets/*', function ($theme) {
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
        Flight::halt(404);
    }

    $requestPath = parse_url(Flight::request()->url, PHP_URL_PATH) ?: '';
    $prefix = '/themes/' . $theme . '/assets/';

    if (!str_starts_with($requestPath, $prefix)) {
        Flight::halt(404);
    }

    $relativeAsset = substr($requestPath, strlen($prefix));

    if ($relativeAsset === '' || str_contains($relativeAsset, '..')) {
        Flight::halt(404);
    }

    $baseDir = dirname(__DIR__) . '/themes/' . $theme . '/assets';
    $baseRealPath = realpath($baseDir);

    if ($baseRealPath === false) {
        Flight::halt(404);
    }

    $targetPath = realpath($baseRealPath . '/' . $relativeAsset);

    if ($targetPath === false || !str_starts_with($targetPath, $baseRealPath . DIRECTORY_SEPARATOR) || !is_file($targetPath)) {
        Flight::halt(404);
    }

    $mime = mime_content_type($targetPath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($targetPath));
    readfile($targetPath);
    exit;
});

// Homepage
Flight::route('GET /', ['App\Controllers\Site\SiteController', 'home']);


// Public pages by slug — SAFE VERSION
Flight::route('GET /@slug', function ($slug) {

    // 🚫 Reserved system paths (add more if needed)
    $reserved = ['admin', 'install', 'api', 'storage', 'themes'];

    // Block exact match
    if (in_array($slug, $reserved, true)) {
        Flight::halt(404);
    }

    // Extra safety for prefixes (future-proof)
    foreach ($reserved as $prefix) {
        if (str_starts_with($slug, $prefix . '/')) {
            Flight::halt(404);
        }
    }

    return (new \App\Controllers\Site\SiteController)->page($slug);
});
