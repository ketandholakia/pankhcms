<?php

namespace App\Controllers\Admin;

use App\Models\Tag;

class TagController
{
    public static function index()
    {
        $tags = Tag::orderBy('name')->get();
        echo \Flight::get('blade')->render('admin.tags.index', compact('tags'));
    }
    public static function store()
    {
           $data = \Flight::request()->data->getData();
        Tag::create($data);
           \Flight::json(['success' => true]);
    }

    public static function update($id)
    {
        $tag = Tag::findOrFail($id);
           $data = \Flight::request()->data->getData();
        $tag->update($data);
           \Flight::json(['success' => true]);
    }

    public static function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();
           \Flight::json(['success' => true]);
    }
}
