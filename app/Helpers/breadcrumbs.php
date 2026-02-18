<?php

use App\Models\Page;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;

if (!function_exists('generateBreadcrumbs')) {
    function generateBreadcrumbs($currentPage = null)
    {
        if (setting('breadcrumbs_enabled', '0') !== '1') {
            return [];
        }

        $crumbs = [];
        $showHome = setting('breadcrumbs_show_home', '1') === '1';
        $homeLabel = setting('breadcrumbs_home_label', 'Home');

        if ($showHome) {
            $crumbs[] = ['title' => $homeLabel, 'url' => '/'];
        }

        if (!$currentPage) {
            return $crumbs;
        }

        $type = setting('breadcrumbs_type', 'auto');

        $pageCrumbs = [];
        $categoryCrumbs = [];
        $menuCrumbs = [];

        if ($type === 'page' || $type === 'auto') {
            $pageCrumbs = pageBreadcrumbs($currentPage);
        }

        if ($type === 'category' || ($type === 'auto' && empty($pageCrumbs))) {
            $categoryCrumbs = categoryBreadcrumbs($currentPage);
        }

        if ($type === 'menu' || ($type === 'auto' && empty($pageCrumbs) && empty($categoryCrumbs))) {
            $menuCrumbs = menuBreadcrumbs($currentPage);
        }

        // Prioritize based on type or auto logic
        if ($type === 'page' && !empty($pageCrumbs)) {
            $crumbs = array_merge($crumbs, $pageCrumbs);
        } elseif ($type === 'category' && !empty($categoryCrumbs)) {
            $crumbs = array_merge($crumbs, $categoryCrumbs);
        } elseif ($type === 'menu' && !empty($menuCrumbs)) {
            $crumbs = array_merge($crumbs, $menuCrumbs);
        } elseif ($type === 'auto') {
            if (!empty($pageCrumbs)) {
                $crumbs = array_merge($crumbs, $pageCrumbs);
            } elseif (!empty($categoryCrumbs)) {
                $crumbs = array_merge($crumbs, $categoryCrumbs);
            } elseif (!empty($menuCrumbs)) {
                $crumbs = array_merge($crumbs, $menuCrumbs);
            }
        }

        return $crumbs;
    }
}

if (!function_exists('pageBreadcrumbs')) {
    function pageBreadcrumbs($page)
    {
        $crumbs = [];
        $current = $page;
        while ($current) {
            array_unshift($crumbs, ['title' => $current->title, 'url' => '/' . $current->slug]);
            $current = $current->parent_id ? Page::find($current->parent_id) : null;
        }
        return $crumbs;
    }
}

if (!function_exists('categoryBreadcrumbs')) {
    function categoryBreadcrumbs($page)
    {
        $crumbs = [];
        $categories = $page->categories; // Assuming Page model has a 'categories' relationship
        if ($categories->isNotEmpty()) {
            $category = $categories->first(); // Pick the first one for simplicity
            $current = $category;
            while ($current) {
                array_unshift($crumbs, ['title' => $current->name, 'url' => '/category/' . $current->slug]);
                $current = $current->parent_id ? Category::find($current->parent_id) : null;
            }
        }
        return $crumbs;
    }
}

if (!function_exists('menuBreadcrumbs')) {
    function menuBreadcrumbs($page)
    {
        // This is a simplified implementation. A full "menu path" would require
        // traversing menu items to find the current page and its ancestors.
        // For now, it just returns the current page as a crumb.
        return [['title' => $page->title, 'url' => '/' . $page->slug]];
    }
}

if (!function_exists('breadcrumbSchema')) {
    function breadcrumbSchema(array $breadcrumbs)
    {
        if (setting('breadcrumbs_schema', '0') !== '1' || empty($breadcrumbs)) {
            return '';
        }

        $listItems = [];
        $position = 1;

        foreach ($breadcrumbs as $crumb) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['title'],
                'item' => env('APP_URL') . $crumb['url'],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}