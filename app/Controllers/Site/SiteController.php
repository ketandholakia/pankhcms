<?php

namespace App\Controllers\Site;

use App\Models\Page;
use App\Models\Category; // Needed for categoryBreadcrumbs
use App\Models\Menu; // Needed for menuBreadcrumbs
use App\Models\MenuItem; // Needed for menuBreadcrumbs
use App\Core\Theme;
use App\Core\VisitTracker;

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
        VisitTracker::track(null, '/');
        // Fallback: render custom home view
        $site_name = \setting('site_name', 'PankhCMS');
        echo \Flight::get('blade')->render('site.home', compact('site_name'));
    }

    public static function page($slug)
    {
        $page = Page::with('categories')->where('slug', $slug)->firstOrFail();
        self::renderPage($page);
    }

    private static function renderPage(Page $page)
    {
        $path = '/' . ltrim((string) ($page->slug ?? ''), '/');
        if ($path === '/') {
            $path = '/';
        }
        VisitTracker::track((int) $page->id, $path);

        $breadcrumbs = \generateBreadcrumbs($page);
        $blocks = [];
        if (!empty($page->content_json)) {
            $blocks = json_decode($page->content_json, true) ?? [];
        }
        $site_name = \setting('site_name', 'PankhCMS');
        echo \Flight::get('blade')->render('page', compact('page', 'breadcrumbs', 'blocks', 'site_name'));
    }
}