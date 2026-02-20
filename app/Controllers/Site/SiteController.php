<?php

namespace App\Controllers\Site;

use App\Models\Page;
use App\Models\Category; // Needed for categoryBreadcrumbs
use App\Models\Menu; // Needed for menuBreadcrumbs
use App\Models\MenuItem; // Needed for menuBreadcrumbs
use App\Core\Theme;

class SiteController
{
    public static function home()
    {
        $homepageId = \setting('homepage_id');
        if ($homepageId) {
            $page = Page::with('categories')->find($homepageId);
            if ($page) {
                return self::renderPage($page);
            }
        }
        // Fallback if no homepage is set or found
        // Create a dummy page object for the home route to generate breadcrumbs
        $page = new Page(['title' => \setting('site_name', 'PankhCMS'), 'slug' => '']);
        self::renderPage($page);
    }

    public static function page($slug)
    {
        $page = Page::with('categories')->where('slug', $slug)->firstOrFail();
        self::renderPage($page);
    }

    private static function renderPage(Page $page)
    {
        $breadcrumbs = \generateBreadcrumbs($page);
        $blocks = [];
        if (!empty($page->content_json)) {
            $blocks = json_decode($page->content_json, true) ?? [];
        }
        $site_name = \setting('site_name', 'PankhCMS');
        echo \Flight::get('blade')->render('page', compact('page', 'breadcrumbs', 'blocks', 'site_name'));
    }
}