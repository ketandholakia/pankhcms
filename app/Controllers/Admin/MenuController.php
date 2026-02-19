<?php

namespace App\Controllers\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Exception;

class MenuController
{
    /**
     * The only fields ever written to the menus table.
     */
    private const FILLABLE = ['name', 'slug', 'description'];

    private const RULES = [
        'name' => ['required' => true,  'max' => 100],
        'slug' => ['required' => false, 'max' => 100],
        'description' => ['required' => false, 'max' => 500],
    ];

    // -------------------------------------------------------------------------
    // Public Actions
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $menus        = Menu::orderBy('name')->get();
        $pages        = Page::orderBy('title')->get();
        $selectedMenu = null;
        $menuItems    = collect();

        $menuId = (int) ($_GET['menu_id'] ?? 0);

        if ($menuId > 0) {
            $selectedMenu = Menu::find($menuId);

            if ($selectedMenu) {
                $flatItems = MenuItem::where('menu_id', $selectedMenu->id)
                    ->orderBy('sort_order')
                    ->get();

                $menuItems = buildMenuTree($flatItems);
            }
        }

        $this->renderView('admin.menus.index', compact(
            'menus', 'selectedMenu', 'menuItems', 'pages'
        ));
    }

    public function store(): void
    {
        $input = $this->getValidatedInput();

        if (isset($input['errors'])) {
            $this->jsonResponse(['errors' => $input['errors']], 422);
            return;
        }

        try {
            $menu = Menu::create($input);
            $this->jsonResponse(['success' => true, 'id' => $menu->id], 201);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to create menu.'], 500);
        }
    }

    public function update(int $id): void
    {
        $menu  = $this->findOrAbort($id);
        $input = $this->getValidatedInput(isUpdate: true);

        if (isset($input['errors'])) {
            $this->jsonResponse(['errors' => $input['errors']], 422);
            return;
        }

        try {
            $menu->update($input);
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to update menu.'], 500);
        }
    }

    public function destroy(int $id): void
    {
        $menu = $this->findOrAbort($id);

        try {
            // Remove all items belonging to this menu before deleting it
            MenuItem::where('menu_id', $menu->id)->delete();
            $menu->delete();

            \Flight::redirect('/admin/menus?deleted=1');
        } catch (Exception $e) {
            \Flight::redirect('/admin/menus?error=delete_failed');
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

        // Validate
        $errors = [];

        foreach (self::RULES as $field => $rules) {
            $value = $data[$field] ?? null;

            if ($rules['required'] && !$isUpdate && ($value === null || $value === '')) {
                $errors[] = "'{$field}' is required.";
                continue;
            }

            if ($value !== null && $rules['max'] !== null && strlen((string) $value) > $rules['max']) {
                $errors[] = "'{$field}' must not exceed {$rules['max']} characters.";
            }
        }

        // Slug format check
        if (!empty($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = "'slug' may only contain lowercase letters, numbers, and hyphens.";
        }

        // Null out empty optionals
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
     * Find a Menu by ID or respond with a JSON 404 and halt.
     * Returns JSON (not a view) because this controller's mutations are API-style.
     */
    private function findOrAbort(int $id): Menu
    {
        $menu = Menu::find($id);

        if (!$menu) {
            $this->jsonResponse(['error' => 'Menu not found.'], 404);
            exit;
        }

        return $menu;
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