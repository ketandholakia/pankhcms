<?php

namespace App\Controllers\Api;

use App\Models\Page;

/**
 * Read-only content API (token-authenticated). Responses use an explicit
 * field whitelist so model internals never leak.
 */
class PageController
{
    /**
     * Fields any API response may expose.
     */
    public const PUBLIC_FIELDS = [
        'id', 'title', 'slug', 'content_json',
        'seo_title', 'seo_description', 'seo_keywords', 'seo_image',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image', 'canonical_url',
        'published_at', 'updated_at',
    ];

    public static function index(): void
    {
        $query = \Flight::request()->query;
        $perPage = max(1, min(100, (int) ($query->per_page ?? 20)));
        $page = max(1, (int) ($query->page ?? 1));

        $base = Page::visible()->select('id', 'title', 'slug', 'updated_at');
        $total = (clone $base)->count();
        $pages = (clone $base)
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        \Flight::json([
            'data' => $pages,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public static function show(string $slug): void
    {
        $page = Page::visible()
            ->where('slug', $slug)
            ->first();

        if (!$page) {
            \Flight::json(['error' => 'Not Found'], 404);
            return;
        }

        \Flight::json(['data' => $page->only(self::PUBLIC_FIELDS)]);
    }
}
