<?php

namespace App\Controllers\Admin;

use Flight;
use App\Models\ContentType;

class ContentTypeController
{
    /**
     * List all content types
     */
    public function index()
    {
        $types = ContentType::orderBy('name')->get();

        Flight::view()->display('admin.content_types.index', [
            'types' => $types
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        Flight::view()->display('admin.content_types.create');
    }

    /**
     * Store new content type
     */
    public function store()
    {
        $req = Flight::request()->data;

        ContentType::create([
            'name' => trim($req->name),
            'slug' => trim($req->slug),
            'description' => $req->description ?? null,
            'icon' => $req->icon ?? null,
            'has_categories' => isset($req->has_categories) ? 1 : 0,
            'has_tags' => isset($req->has_tags) ? 1 : 0,
            'is_system' => 0,
        ]);

        Flight::redirect('/admin/content-types');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $type = ContentType::findOrFail($id);

        Flight::view()->display('admin.content_types.edit', [
            'type' => $type
        ]);
    }

    /**
     * Update content type
     */
    public function update($id)
    {
        $type = ContentType::findOrFail($id);

        if ($type->is_system) {
            Flight::halt(403, 'System content types cannot be edited.');
        }

        $req = Flight::request()->data;

        $type->update([
            'name' => trim($req->name),
            'slug' => trim($req->slug),
            'description' => $req->description ?? null,
            'icon' => $req->icon ?? null,
            'has_categories' => isset($req->has_categories) ? 1 : 0,
            'has_tags' => isset($req->has_tags) ? 1 : 0,
        ]);

        Flight::redirect('/admin/content-types');
    }

    /**
     * Delete content type
     */
    public function delete($id)
    {
        $type = ContentType::findOrFail($id);

        if ($type->is_system) {
            Flight::halt(403, 'System content types cannot be deleted.');
        }

        $type->delete();

        Flight::redirect('/admin/content-types');
    }
}
