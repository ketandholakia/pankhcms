<?php

namespace App\Controllers\Admin;

use App\Models\MenuItem;

class MenuItemController
{

    public static function store()
    {
        $data = \Flight::request()->data->getData();
        $menuId = $data['menu_id'] ?? null;
        $parentId = $data['parent_id'] ?? null;
        $pageId = $data['page_id'] ?? null;
        $url = $data['url'] ?? null;

        if ($pageId) {
            $url = null;
        } else {
            $pageId = null;
        }

        $maxOrder = MenuItem::where('menu_id', $menuId)
            ->where('parent_id', $parentId)
            ->max('sort_order');

        $data['sort_order'] = is_null($maxOrder) ? 0 : $maxOrder + 1;
        $data['page_id'] = $pageId;
        $data['url'] = $url;

        MenuItem::create($data);
        \Flight::json(['success' => true]);
    }

    public static function update($id)
    {
        $item = \App\Models\MenuItem::findOrFail($id);
        $data = \Flight::request()->data->getData();
        $pageId = $data['page_id'] ?? null;
        $url = $data['url'] ?? null;

        if ($pageId) {
            $url = null;
        } else {
            $pageId = null;
        }

        $data['page_id'] = $pageId;
        $data['url'] = $url;
        $item->update($data);
        \Flight::json(['success' => true]);
    }

    public static function move($id)
    {
        $item = \App\Models\MenuItem::findOrFail($id);
        $direction = $_POST['direction'] ?? null;
        if (!$direction) return \Flight::json(['error' => 'No direction'], 400);
        $siblings = \App\Models\MenuItem::where('menu_id', $item->menu_id)
            ->where('parent_id', $item->parent_id)
            ->orderBy('sort_order')
            ->get();
        $index = $siblings->search(fn($i) => $i->id == $item->id);
        if ($direction === 'up' && $index > 0) {
            $prev = $siblings[$index - 1];
            $tmp = $item->sort_order;
            $item->sort_order = $prev->sort_order;
            $prev->sort_order = $tmp;
            $item->save();
            $prev->save();
        } elseif ($direction === 'down' && $index < $siblings->count() - 1) {
            $next = $siblings[$index + 1];
            $tmp = $item->sort_order;
            $item->sort_order = $next->sort_order;
            $next->sort_order = $tmp;
            $item->save();
            $next->save();
        }
        \Flight::redirect($_SERVER['HTTP_REFERER'] ?? '/admin/menus?menu_id=' . $item->menu_id);
    }

    public static function destroy($id)
    {
        $item = \App\Models\MenuItem::findOrFail($id);
        $item->delete();
        \Flight::redirect($_SERVER['HTTP_REFERER'] ?? '/admin/menus?menu_id=' . $item->menu_id);
    }
}
