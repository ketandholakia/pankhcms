<?php

namespace App\Controllers\Admin;

use App\Models\Tag;
use Exception;

class TagController
{
    /**
     * The only fields ever written to the tags table.
     */
    private const FILLABLE = ['name', 'slug', 'description'];

    private const RULES = [
        'name'        => ['required' => true,  'max' => 100],
        'slug'        => ['required' => false, 'max' => 100],
        'description' => ['required' => false, 'max' => 500],
    ];

    // -------------------------------------------------------------------------
    // Public Actions
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $tags = Tag::orderBy('name')->get();

        $this->renderView('admin.tags.index', compact('tags'));
    }

    public function store(): void
    {
        $input = $this->getValidatedInput();

        if (isset($input['errors'])) {
            $this->jsonResponse(['errors' => $input['errors']], 422);
            return;
        }

        try {
            $tag = Tag::create($input);
            $this->jsonResponse(['success' => true, 'id' => $tag->id], 201);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to create tag.'], 500);
        }
    }

    public function update(int $id): void
    {
        $tag   = $this->findOrAbort($id);
        $input = $this->getValidatedInput(isUpdate: true);

        if (isset($input['errors'])) {
            $this->jsonResponse(['errors' => $input['errors']], 422);
            return;
        }

        try {
            $tag->update($input);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to update tag.'], 500);
        }
    }

    public function destroy(int $id): void
    {
        $tag = $this->findOrAbort($id);

        try {
            $tag->delete();
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to delete tag.'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Whitelist, sanitize, and validate POST input.
     *
     * @return array Clean data, or ['errors' => string[]] on failure.
     */
    private function getValidatedInput(bool $isUpdate = false): array
    {
        $raw  = $this->rawInput();
        $data = array_intersect_key($raw, array_flip(self::FILLABLE));

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        $errors = [];

        foreach (self::RULES as $field => $rules) {
            $value = $data[$field] ?? null;

            if (($rules['required'] ?? false) && !$isUpdate && ($value === null || $value === '')) {
                $errors[] = "'{$field}' is required.";
                continue;
            }

            if (isset($rules['max']) && $value !== null && strlen((string) $value) > $rules['max']) {
                $errors[] = "'{$field}' must not exceed {$rules['max']} characters.";
            }
        }

        if (!empty($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = "'slug' may only contain lowercase letters, numbers, and hyphens.";
        }

        foreach (['description', 'slug'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $errors ? ['errors' => $errors] : $data;
    }

    /**
     * Return raw POST data via Flight's request object.
     */
    private function rawInput(): array
    {
        return (array) \Flight::request()->data->getData();
    }

    /**
     * Find a Tag by ID or respond with JSON 404 and halt.
     */
    private function findOrAbort(int $id): Tag
    {
        $tag = Tag::find($id);

        if (!$tag) {
            $this->jsonResponse(['error' => 'Tag not found.'], 404);
            exit;
        }

        return $tag;
    }

    /**
     * Send a JSON response with an HTTP status code.
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        \Flight::response()->status($status);
        \Flight::json($data);
    }

    /**
     * Render a Blade view.
     */
    private function renderView(string $view, array $data = []): void
    {
        echo \Flight::get('blade')->render($view, $data);
    }
}