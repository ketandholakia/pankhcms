<?php

Flight::group('/api/v1', function () {
    
    Flight::route('GET /status', function () {
        Flight::json(['status' => 'ok', 'version' => '1.0.0']);
    });

    // Content API
    Flight::route('GET /pages', function () {
        $pages = \Illuminate\Database\Capsule\Manager::table('pages')
            ->where('status', 'published')
            ->select('id', 'title', 'slug', 'updated_at')
            ->get();
        Flight::json(['data' => $pages]);
    });
    
    Flight::route('GET /pages/@slug', function ($slug) {
        $page = \Illuminate\Database\Capsule\Manager::table('pages')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->first();
            
        if (!$page) {
            Flight::json(['error' => 'Not Found'], 404);
            return;
        }
        
        Flight::json(['data' => $page]);
    });

});
