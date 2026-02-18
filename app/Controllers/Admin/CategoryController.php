<?php

namespace App\Controllers\Admin;

use App\Models\Category;

class CategoryController
{
    public static function index()
    {
        $categories = Category::orderBy('name')->get();
        echo \Flight::get('blade')->render('admin.categories.index', compact('categories'));
    }
    public static function store()
    {
        $data = \Flight::request()->data->getData();
        Category::create($data);
        \Flight::json(['success' => true]);
    }

    public static function update($id)
    {
        $cat = Category::findOrFail($id);
        $data = \Flight::request()->data->getData();
        $cat->update($data);
        \Flight::json(['success' => true]);
    }

    public static function destroy($id)
    {
        $cat = Category::findOrFail($id);
        $cat->delete();
        \Flight::json(['success' => true]);
    }
}
