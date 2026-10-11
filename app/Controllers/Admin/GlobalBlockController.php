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
            'content' => $this->sanitizeContent($data['content'] ?? ''),
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
            'show_title' => isset($data['show_title']) ? (bool)$data['show_title'] : true,
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
            'content' => array_key_exists('content', $data)
                ? $this->sanitizeContent($data['content'])
                : $block->content,
            'status' => isset($data['status']) ? (int)$data['status'] : $block->status,
            'show_title' => isset($data['show_title']) ? (bool)$data['show_title'] : $block->show_title,
        ]);
        Flight::json(['success' => true]);
    }
    /**
     * String block content is sanitized with the allowlist sanitizer before
     * it is stored. Structured (JSON) content keeps its shape and its
     * rich-text fields are sanitized at render time.
     */
    private function sanitizeContent($content)
    {
        if (!is_string($content)) {
            return $content;
        }

        if (json_decode($content, true) !== null) {
            // Looks like structured JSON; sanitize its html/text fields too.
            return \App\Core\HtmlSanitizer::sanitizeBlocksJson($content);
        }

        return sanitize_html($content);
    }

    // Delete block
    public function delete($id)
    {
        $block = GlobalBlock::findOrFail($id);
        $block->delete();
        Flight::json(['success' => true]);
    }
}
