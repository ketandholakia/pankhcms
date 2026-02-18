<?php

namespace App\Controllers\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;

class MenuController
{
    public static function index()
    {
        $menus = Menu::all();
        $selectedMenu = null;
        $menuItems = collect();
        $pages = Page::all();
        $menuId = $_GET['menu_id'] ?? null;
        if ($menuId) {
            $selectedMenu = Menu::find($menuId);
            if ($selectedMenu) {
                $flatItems = MenuItem::where('menu_id', $selectedMenu->id)
                    ->orderBy('sort_order')
                    ->get();
                $menuItems = \buildMenuTree($flatItems);
            }
        }
        echo \Flight::get('blade')->render('admin.menus.index', compact('menus', 'selectedMenu', 'menuItems', 'pages'));
    }
    public static function update($id)
    {
        $menu = Menu::findOrFail($id);
        $data = \Flight::request()->data->getData();
        $menu->update($data);
        \Flight::json(['success' => true]);
    }

    public static function store()
    {
        $data = \Flight::request()->data->getData();
        $menu = Menu::create($data);
        \Flight::json(['success' => true, 'id' => $menu->id]);
    }

    public static function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        \Flight::redirect('/admin/menus');
    }
}
