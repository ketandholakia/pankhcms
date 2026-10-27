<?php

namespace App\Controllers\Site;

use App\Models\Page;
use App\Models\Category; // Needed for categoryBreadcrumbs
use App\Models\Menu; // Needed for menuBreadcrumbs
use App\Models\MenuItem; // Needed for menuBreadcrumbs
use App\Models\SliderImage;
use App\Core\Theme;

class SiteController
{
    private static function getPageQuery()
    {
        $query = Page::with('categories');

        // Draft preview requires an authenticated user with manage_pages;
        // everyone else only sees published/scheduled-visible pages.
        $canPreview = false;
        if (isset(\Flight::request()->query->preview) && \Flight::request()->query->preview) {
            $user = \App\Core\Auth::user();
            $canPreview = $user !== null && $user->hasPermission('manage_pages');
        }

        if (!$canPreview) {
            $query->visible();
        }
        return $query;
    }

    public static function home()
    {
        $homepageId = \setting('homepage_id');
        if ($homepageId) {
            $page = self::getPageQuery()->where('id', $homepageId)->first();
            if ($page) {
                return self::renderPage($page);
            }
        }
        // View counting happens exclusively via the POST /_v beacon
        // (VisitTracker::handleBeacon) injected by the public layouts,
        // so cached and uncached renders are counted exactly once.
        // Fallback: render custom home view
        $site_name = \setting('site_name', 'PankhCMS');
        $sliders = SliderImage::where('active', 1)->orderBy('sort_order')->get();
        echo \Flight::get('blade')->render('site.home', compact('site_name', 'sliders'));
    }

    public static function page($slug)
    {
        $page = self::getPageQuery()->where('slug', $slug)->first();
        if (!$page) {
            \Flight::halt(404);
            return;
        }
        self::renderPage($page);
    }

    /**
     * Public blog index: newest-first list of published posts, 10 per
     * page (?page=N), same hand-rolled paginator shape as the admin.
     */
    public static function blog()
    {
        $perPage = 10;
        $pageNum = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $query = Page::visible()->where('type', 'post');
        $total = (int) $query->count();

        $posts = $query->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->skip(($pageNum - 1) * $perPage)
            ->take($perPage)
            ->get();

        $paginator = [
            'current' => $pageNum,
            'last' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'perPage' => $perPage,
        ];

        echo \Flight::get('blade')->render('site.blog', [
            'posts' => $posts,
            'paginator' => $paginator,
            'site_name' => \setting('site_name', 'PankhCMS'),
        ]);
    }

    private static function renderPage(Page $page)
    {
        $path = '/' . ltrim((string) ($page->slug ?? ''), '/');
        if ($path === '/') {
            $path = '/';
        }
        // View counting happens exclusively via the POST /_v beacon
        // (VisitTracker::handleBeacon) injected by the public layouts,
        // so cached and uncached renders are counted exactly once.

        $breadcrumbs = \generateBreadcrumbs($page);
        $blocks = [];
        if (!empty($page->content_json)) {
            $blocks = json_decode($page->content_json, true) ?? [];
        }
        $site_name = \setting('site_name', 'PankhCMS');
        $blade = \Flight::get('blade');

        $homepageId = (int) (\setting('homepage_id') ?: 0);
        $isHome = $homepageId > 0 && (int) $page->id === $homepageId;

        $sliders = $isHome
            ? SliderImage::where('active', 1)->orderBy('sort_order')->get()
            : collect();

        $type = trim((string) ($page->type ?? ''));
        $candidateViews = [];

        // Home page can have its own template
        if ($isHome) {
            $candidateViews[] = 'templates.home';
            $candidateViews[] = 'site.home';
        }

        $layout = trim((string) ($page->layout ?? ''));
        if ($layout !== '' && $layout !== 'default') {
            $candidateViews[] = 'templates.' . $layout;
        }

        if ($type !== '' && $type !== 'page') {
            $candidateViews[] = 'templates.' . $type;
            $candidateViews[] = 'site.' . $type;

            $singularType = (substr($type, -1) === 's' && strlen($type) > 1)
                ? substr($type, 0, -1)
                : $type;

            if ($singularType !== $type) {
                $candidateViews[] = 'templates.' . $singularType;
                $candidateViews[] = 'site.' . $singularType;
            }
        }

        $candidateViews[] = 'templates.page';
        $candidateViews[] = 'site.page';
        $candidateViews[] = 'page';

        $viewToRender = 'page';
        foreach ($candidateViews as $viewName) {
            if ($blade->exists($viewName)) {
                $viewToRender = $viewName;
                break;
            }
        }

        echo $blade->render($viewToRender, compact('page', 'breadcrumbs', 'blocks', 'site_name', 'sliders'));
    }
}
