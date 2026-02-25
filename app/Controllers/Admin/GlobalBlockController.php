<?php
namespace App\Controllers\Admin;

use App\Models\GlobalBlock;
use App\Models\BlockPlacement;
use Flight;

class GlobalBlockController
{
    // List all global blocks
    public function index()
    {
        $blocks = GlobalBlock::orderBy('updated_at', 'desc')->get();
        echo \Flight::get('blade')->render('admin.global_blocks.index', compact('blocks'));
    }

    // Show create form
    public function create()
    {
        echo \Flight::get('blade')->render('admin.global_blocks.create');
    }

    // Store new block
    public function store()
    {
        $data = Flight::request()->data->getData();
        $block = GlobalBlock::create([
            'title' => $data['title'] ?? '',
            'type' => $data['type'] ?? 'text',
            'location' => $data['location'] ?? '',
            'content' => $data['content'] ?? '',
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
        ]);
        Flight::json(['success' => true, 'id' => $block->id]);
    }

    // Show edit form
    public function edit($id)
    {
        $block = GlobalBlock::findOrFail($id);
        echo \Flight::get('blade')->render('admin.global_blocks.edit', compact('block'));
    }

    // Update block
    public function update($id)
    {
        $block = GlobalBlock::findOrFail($id);
        $data = Flight::request()->data->getData();
        $block->update([
            'title' => $data['title'] ?? $block->title,
            'type' => $data['type'] ?? $block->type,
            'location' => $data['location'] ?? $block->location,
            'content' => $data['content'] ?? $block->content,
            'status' => isset($data['status']) ? (int)$data['status'] : $block->status,
        ]);
        Flight::json(['success' => true]);
    }

    // Delete block
    public function delete($id)
    {
        $block = GlobalBlock::findOrFail($id);
        $block->delete();
        Flight::json(['success' => true]);
    }
}
