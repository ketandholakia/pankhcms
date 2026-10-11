<?php

Flight::group('/api/v1', function () {
    
    Flight::route('GET /status', function () {
        Flight::json(['status' => 'ok', 'version' => '1.0.0']);
    });

    // Content API
    Flight::route('GET /pages', function () {
        $query = \Flight::request()->query;
        $perPage = max(1, min(100, (int) ($query->per_page ?? 20)));
        $page = max(1, (int) ($query->page ?? 1));

        $base = \App\Models\Page::visible()->select('id', 'title', 'slug', 'updated_at');
        $total = (clone $base)->count();
        $pages = (clone $base)
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        Flight::json([
            'data' => $pages,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
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
