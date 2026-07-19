<?php

Flight::group('/api/v1', function () {
    
    Flight::route('GET /status', function () {
        Flight::json(['status' => 'ok', 'version' => '1.0.0']);
    });

    // Content API
    Flight::route('GET /pages', function () {
        $pages = \App\Models\Page::visible()
            ->select('id', 'title', 'slug', 'updated_at')
            ->get();
        Flight::json(['data' => $pages]);
    })->addMiddleware([new \App\Middleware\RequireApiToken()]);
    
    Flight::route('GET /pages/@slug', function ($slug) {
        $page = \App\Models\Page::visible()
            ->where('slug', $slug)
            ->first();
            
        if (!$page) {
            Flight::json(['error' => 'Not Found'], 404);
            return;
        }
        
        Flight::json(['data' => $page]);
    })->addMiddleware([new \App\Middleware\RequireApiToken()]);

});
