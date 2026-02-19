<?php

namespace App\Controllers\Admin;

use App\Models\ContentType;
use Exception;
use Flight;

class ContentTypeController
{
    /**
     * The only fields ever written to the content_types table.
     */
    private const FILLABLE = [
        'name', 'slug', 'description', 'icon', 'has_categories', 'has_tags',
    ];

    private const RULES = [
        'name' => ['required' => true,  'max' => 100],
        'slug' => ['required' => true,  'max' => 100],
        'description' => ['required' => false, 'max' => 500],
        'icon' => ['required' => false, 'max' => 100],
    ];

    // -------------------------------------------------------------------------
    // Public Actions
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $types = ContentType::orderBy('name')->get();
        $this->renderView('admin.content_types.index', compact('types'));
    }

    public function create(): void
    {
        $this->renderView('admin.content_types.create');
    }

    public function store(): void
    {
        $input = $this->getValidatedInput();

        if (isset($input['errors'])) {
            $this->renderView('admin.content_types.create', [
                'errors' => $input['errors'],
                'old'    => $this->rawInput(),
            ]);
            return;
        }

        try {
            ContentType::create($input + ['is_system' => 0]);
            Flight::redirect('/admin/content-types?created=1');
        } catch (Exception $e) {
            $this->renderView('admin.content_types.create', [
                'errors' => ['An unexpected error occurred. Please try again.'],
                'old'    => $this->rawInput(),
            ]);
        }
    }

    public function edit(int $id): void
    {
        $type = $this->findOrAbort($id);

        $this->renderView('admin.content_types.edit', compact('type'));
    }

    public function update(int $id): void
    {
        $type = $this->findOrAbort($id);

        $this->abortIfSystem($type, 'edited');

        $input = $this->getValidatedInput(isUpdate: true);

        if (isset($input['errors'])) {
            $this->renderView('admin.content_types.edit', [
                'errors' => $input['errors'],
                'old'    => $this->rawInput(),
                'type'   => $type,
            ]);
            return;
        }

        try {
            $type->update($input);
            Flight::redirect('/admin/content-types?updated=1');
        } catch (Exception $e) {
            $this->renderView('admin.content_types.edit', [
                'errors' => ['An unexpected error occurred. Please try again.'],
                'old'    => $this->rawInput(),
                'type'   => $type,
            ]);
        }
    }

    public function destroy(int $id): void
    {
        $type = $this->findOrAbort($id);

        $this->abortIfSystem($type, 'deleted');

        try {
            $type->delete();
            Flight::redirect('/admin/content-types?deleted=1');
        } catch (Exception $e) {
            Flight::redirect('/admin/content-types?error=delete_failed');
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

        // Coerce checkboxes to integers (unchecked boxes are absent from POST)
        $data['has_categories'] = isset($raw['has_categories']) ? 1 : 0;
        $data['has_tags']       = isset($raw['has_tags'])       ? 1 : 0;

        // Null out empty optionals
        foreach (['description', 'icon'] as $field) {
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
        return (array) Flight::request()->data->getData();
    }

    /**
     * Find a ContentType by ID or respond with 404 and halt.
     */
    private function findOrAbort(int $id): ContentType
    {
        $type = ContentType::find($id);

        if (!$type) {
            Flight::response()->status(404);
            $this->renderView('admin.errors.404', ['message' => 'Content type not found.']);
            exit;
        }

        return $type;
    }

    /**
     * Halt with 403 if the content type is a protected system record.
     *
     * @param string $action Human-readable action name for the error message (e.g. 'edited').
     */
    private function abortIfSystem(ContentType $type, string $action): void
    {
        if ($type->is_system) {
            Flight::response()->status(403);
            $this->renderView('admin.errors.403', [
                'message' => "System content types cannot be {$action}.",
            ]);
            exit;
        }
    }

    /**
     * Render a Blade/Flight view.
     */
    private function renderView(string $view, array $data = []): void
    {
        // If the view name contains '.blade', use Blade, else use Flight's view engine
        if (strpos($view, '.blade') !== false || file_exists(dirname(__DIR__, 3) . "/views/" . str_replace('.', '/', $view) . ".blade.php")) {
            echo Flight::get('blade')->render($view, $data);
        } else {
            Flight::view()->render($view, $data);
        }
    }
}