<?php

Flight::group('/api/v1', function () {

    Flight::route('GET /status', function () {
        Flight::json(['status' => 'ok', 'version' => '1.0.0']);
    });

    // Content API
    Flight::route('GET /pages', [\App\Controllers\Api\PageController::class, 'index'])
        ->addMiddleware([new \App\Middleware\RequireApiToken()]);

    Flight::route('GET /pages/@slug', [\App\Controllers\Api\PageController::class, 'show'])
        ->addMiddleware([new \App\Middleware\RequireApiToken()]);

});
