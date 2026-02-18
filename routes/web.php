<?php

// Public Contact Form Routes
Flight::route('GET /contact', ['App\Controllers\ContactController', 'show']);
Flight::route('POST /contact', ['App\Controllers\ContactController', 'submit']);

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
