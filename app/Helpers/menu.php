<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;

if (!function_exists('render_menu')) {
    function render_menu($location = 'header')
    {
        $menu = Menu::where('location', $location)->first();
        if (!$menu) return '';
        $items = MenuItem::where('menu_id', $menu->id)->orderBy('sort_order')->get();
        $tree = build_menu_tree($items);
        return \Flight::get('blade')->render('site.menu', compact('tree'));
    }
}

if (!function_exists('menu_tree')) {
    function menu_tree($location = 'header')
    {
        $menu = Menu::where('location', $location)->first();
        if (!$menu) return [];
        $items = MenuItem::where('menu_id', $menu->id)->orderBy('sort_order')->get();
        return build_menu_tree($items);
    }
}
