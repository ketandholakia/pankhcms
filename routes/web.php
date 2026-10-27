<?php

// Public Contact Form Routes
Flight::route('GET /contact', [\App\Controllers\ContactController::class, 'show']);
Flight::route('POST /contact', [\App\Controllers\ContactController::class, 'submit']);

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

    // MIME by extension first: mime_content_type can misreport css/js.
    $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $mimeByExtension = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'txt' => 'text/plain; charset=utf-8',
    ];
    $mime = $mimeByExtension[$extension] ?? (mime_content_type($targetPath) ?: 'application/octet-stream');

    $mtime = filemtime($targetPath) ?: time();
    $lastModified = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && trim((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModified) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($targetPath));
    header('Cache-Control: public, max-age=2592000, immutable');
    header('Last-Modified: ' . $lastModified);
    readfile($targetPath);
    exit;
});

Flight::route('GET /sitemap.xml', function () {
    header('Content-Type: application/xml; charset=utf-8');

    $pages = \App\Models\Page::query()
        ->visible()
        ->where(function ($query) {
            $query->where('type', 'page')->orWhereNull('type');
        })
        ->orderBy('updated_at', 'desc')
        ->get();

    $posts = \App\Models\Page::query()
        ->visible()
        ->where('type', 'post')
        ->orderBy('updated_at', 'desc')
        ->get();

    $products = \App\Models\Page::query()
        ->visible()
        ->whereIn('type', ['product', 'products'])
        ->orderBy('updated_at', 'desc')
        ->get();

    $categories = [];
    try {
        if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('categories')) {
            $categories = \App\Models\Category::orderBy('id', 'desc')->get();
        }
    } catch (\Throwable $e) {
        $categories = [];
    }

    echo \Flight::get('blade')->render('sitemap', compact('pages', 'posts', 'products', 'categories'));
});

Flight::route('GET /robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    $siteUrl = rtrim((string) (seo_setting('site_url', 'canonical_base', env('APP_URL', ''))), '/');

    echo "User-agent: *\n";
    echo "Allow: /\n\n";
    if ($siteUrl !== '') {
        echo "Sitemap: {$siteUrl}/sitemap.xml\n";
    }
});

// Homepage
Flight::route('GET /', [\App\Controllers\Site\SiteController::class, 'home']);

// Blog index: registered BEFORE the /@slug catch-all so a page slug
// can never shadow it (and 'blog' is a reserved page slug).
Flight::route('GET /blog', [\App\Controllers\Site\SiteController::class, 'blog']);


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
