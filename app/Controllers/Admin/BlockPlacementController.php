<?php
namespace App\Controllers\Admin;

use App\Models\BlockPlacement;
use App\Models\GlobalBlock;
use Flight;

class BlockPlacementController {
    public function index() {
        $placements = BlockPlacement::with('block')->orderBy('order')->get();
        $blocks = GlobalBlock::orderBy('title')->get();
        echo \Flight::get('blade')->render('admin.block_placements.index', [
            'placements' => $placements,
            'blocks' => $blocks,
        ]);
    }

    public function create() {
        $blocks = GlobalBlock::all();
        echo \Flight::get('blade')->render('admin.block_placements.create', [
            'blocks' => $blocks
        ]);
    }

    public function store() {
        $data = Flight::request()->data->getData();
        $placement = BlockPlacement::create([
            'block_id' => $data['block_id'],
            'page_id' => $data['page_id'] ?? null,
            'section' => $data['location'] ?? $data['section'] ?? null,
            'order' => isset($data['sort_order']) ? (int)$data['sort_order'] : (isset($data['order']) ? (int)$data['order'] : 0),
        ]);
        Flight::json(['success' => true, 'id' => $placement->id]);
    }

    public function edit($id) {
        $placement = BlockPlacement::findOrFail($id);
        $blocks = GlobalBlock::orderBy('title')->get();
        echo \Flight::get('blade')->render('admin.block_placements.edit', [
            'placement' => $placement,
            'blocks' => $blocks
        ]);
    }

    public function update($id) {
        $placement = BlockPlacement::findOrFail($id);
        $data = Flight::request()->data->getData();
        $placement->update([
            'block_id' => $data['block_id'],
            'page_id' => $data['page_id'] ?? null,
            'section' => $data['location'] ?? $data['section'] ?? null,
            'order' => isset($data['sort_order']) ? (int)$data['sort_order'] : (isset($data['order']) ? (int)$data['order'] : $placement->order),
        ]);
        Flight::json(['success' => true]);
    }

    public function destroy($id) {
        $placement = BlockPlacement::findOrFail($id);
        $placement->delete();
        Flight::json(['success' => true]);
    }
}
