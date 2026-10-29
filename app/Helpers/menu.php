<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;

if (!function_exists('eager_load_menu_page_slugs')) {
    /**
     * Attach each menu item's page slug in ONE query, so templates never
     * run Page::find() per item.
     */
    function eager_load_menu_page_slugs($items): void
    {
        $pageIds = [];
        foreach ($items as $item) {
            if (!empty($item->page_id)) {
                $pageIds[(int) $item->page_id] = true;
            }
        }
        if ($pageIds === []) {
            return;
        }

        $slugs = Page::whereIn('id', array_keys($pageIds))->pluck('slug', 'id');
        foreach ($items as $item) {
            $item->page_slug = !empty($item->page_id) ? (string) ($slugs[(int) $item->page_id] ?? '') : '';
        }
    }
}

if (!function_exists('render_menu')) {
    function render_menu($location = 'header')
    {
        $menu = Menu::where('location', $location)->first();
        if (!$menu) return '';
        $items = MenuItem::where('menu_id', $menu->id)->orderBy('sort_order')->get();
        eager_load_menu_page_slugs($items);
        $tree = build_menu_tree($items);
        return \Flight::get('blade')->render('site.menu', compact('tree'));
    }
}

if (!function_exists('menu')) {
    function menu($location = 'header')
    {
        return render_menu($location);
    }
}

if (!function_exists('menu_tree')) {
    function menu_tree($location = 'header')
    {
        $menu = Menu::where('location', $location)->first();
        if (!$menu) return [];
        $items = MenuItem::where('menu_id', $menu->id)->orderBy('sort_order')->get();
        eager_load_menu_page_slugs($items);
        return build_menu_tree($items);
    }
}
