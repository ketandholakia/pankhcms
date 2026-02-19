<?php

namespace App\Controllers\Admin;

use App\Models\MenuItem;
use Exception;

class MenuItemController
{
    /**
     * The only fields ever written to the menu_items table.
     */
    private const FILLABLE = [
        'menu_id', 'parent_id', 'page_id', 'url', 'label', 'target', 'sort_order',
    ];

    private const RULES = [
        'menu_id' => ['required' => true],
        'label'   => ['required' => true, 'max' => 150],
        'url'     => ['required' => false, 'max' => 2048],
        'target'  => ['required' => false, 'allowed' => ['_self', '_blank']],
    ];

    // -------------------------------------------------------------------------
    // Public Actions
    // -------------------------------------------------------------------------

    public function store(): void
    {
        $input = $this->getValidatedInput();

        if (isset($input['errors'])) {
            $this->jsonResponse(['errors' => $input['errors']], 422);
            return;
        }

        try {
            [$pageId, $url] = $this->resolvePageOrUrl($input);

            $maxOrder = MenuItem::where('menu_id', $input['menu_id'])
                ->where('parent_id', $input['parent_id'] ?? null)
                ->max('sort_order');

            $item = MenuItem::create(array_merge($input, [
                'page_id'    => $pageId,
                'url'        => $url,
                'sort_order' => is_null($maxOrder) ? 0 : $maxOrder + 1,
            ]));

            $this->jsonResponse(['success' => true, 'id' => $item->id], 201);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to create menu item.'], 500);
        }
    }

    public function update(int $id): void
    {
        $item  = $this->findOrAbort($id);
        $input = $this->getValidatedInput(isUpdate: true);

        if (isset($input['errors'])) {
            $this->jsonResponse(['errors' => $input['errors']], 422);
            return;
        }

        try {
            [$pageId, $url] = $this->resolvePageOrUrl($input);

            $item->update(array_merge($input, [
                'page_id' => $pageId,
                'url'     => $url,
            ]));

            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to update menu item.'], 500);
        }
    }

    public function move(int $id): void
    {
        $item      = $this->findOrAbort($id);
        $direction = $this->rawInput()['direction'] ?? null;

        if (!in_array($direction, ['up', 'down'], strict: true)) {
            $this->jsonResponse(['error' => "Direction must be 'up' or 'down'."], 400);
            return;
        }

        try {
            $siblings = MenuItem::where('menu_id', $item->menu_id)
                ->where('parent_id', $item->parent_id)
                ->orderBy('sort_order')
                ->get();

            $index = $siblings->search(fn($s) => $s->id === $item->id);

            if ($direction === 'up' && $index > 0) {
                $this->swapSortOrder($item, $siblings[$index - 1]);
            } elseif ($direction === 'down' && $index < $siblings->count() - 1) {
                $this->swapSortOrder($item, $siblings[$index + 1]);
            }

            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to move menu item.'], 500);
        }
    }

    public function destroy(int $id): void
    {
        $item = $this->findOrAbort($id);

        try {
            // Re-home any children of this item to its parent so they aren't orphaned
            MenuItem::where('parent_id', $item->id)
                ->update(['parent_id' => $item->parent_id]);

            $item->delete();

            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to delete menu item.'], 500);
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

        // Trim strings
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

            if (isset($rules['allowed']) && $value !== null && !in_array($value, $rules['allowed'], strict: true)) {
                $errors[] = "'{$field}' must be one of: " . implode(', ', $rules['allowed']) . '.';
            }
        }

        // Validate IDs are positive integers when present
        foreach (['menu_id', 'parent_id', 'page_id'] as $idField) {
            if (!empty($data[$idField])) {
                $data[$idField] = (int) $data[$idField];
                if ($data[$idField] < 1) {
                    $errors[] = "'{$idField}' must be a positive integer.";
                }
            } else {
                $data[$idField] = null;
            }
        }

        return $errors ? ['errors' => $errors] : $data;
    }

    /**
     * A menu item links to either a page (internal) or a URL (external) — never both.
     * page_id takes priority; if set, url is cleared, and vice versa.
     *
     * @return array{int|null, string|null} [$pageId, $url]
     */
    private function resolvePageOrUrl(array $input): array
    {
        $pageId = !empty($input['page_id']) ? (int) $input['page_id'] : null;
        $url    = !empty($input['url'])     ? $input['url']           : null;

        if ($pageId !== null) {
            $url = null;
        } else {
            $pageId = null;
        }

        return [$pageId, $url];
    }

    /**
     * Atomically swap sort_order values between two sibling items.
     */
    private function swapSortOrder(MenuItem $a, MenuItem $b): void
    {
        $tmp          = $a->sort_order;
        $a->sort_order = $b->sort_order;
        $b->sort_order = $tmp;

        $a->save();
        $b->save();
    }

    /**
     * Return raw POST/request data via Flight.
     */
    private function rawInput(): array
    {
        return (array) \Flight::request()->data->getData();
    }

    /**
     * Find a MenuItem by ID or respond with JSON 404 and halt.
     */
    private function findOrAbort(int $id): MenuItem
    {
        $item = MenuItem::find($id);

        if (!$item) {
            $this->jsonResponse(['error' => 'Menu item not found.'], 404);
            exit;
        }

        return $item;
    }

    /**
     * Send a JSON response with an HTTP status code.
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        \Flight::response()->status($status);
        \Flight::json($data);
    }
}