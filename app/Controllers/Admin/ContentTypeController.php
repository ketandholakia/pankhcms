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
        $fields = $type->fields()->orderBy('sort_order')->get();
        $this->renderView('admin.content_types.edit', compact('type', 'fields'));
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

    public function saveFields(int $id): void
    {
        $type = $this->findOrAbort($id);
        $payload = (array) Flight::request()->data->getData();
        $fields = $payload['fields'] ?? [];
        $newField = $payload['new_field'] ?? [];

        foreach ($fields as $fieldId => $data) {
            $field = $type->fields()->find($fieldId);
            if (!$field) {
                continue;
            }

            if (!empty($data['delete'])) {
                $field->delete();
                continue;
            }

            $field->label = $data['label'] ?? $field->label;
            $field->name = $data['name'] ?? $field->name;
            $field->type = $data['type'] ?? $field->type;
            $field->required = !empty($data['required']) ? 1 : 0;
            $field->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
            $field->save();
        }

        if (!empty($newField['label']) && !empty($newField['name'])) {
            $type->fields()->create([
                'label' => trim((string) $newField['label']),
                'name' => trim((string) $newField['name']),
                'type' => $newField['type'] ?? 'text',
                'required' => !empty($newField['required']) ? 1 : 0,
                'sort_order' => isset($newField['sort_order']) ? (int) $newField['sort_order'] : 0,
            ]);
        }

        Flight::redirect("/admin/content-types/{$id}/edit?fields_updated=1");
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

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

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

        if (!empty($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = "'slug' may only contain lowercase letters, numbers, and hyphens.";
        }

        $data['has_categories'] = isset($raw['has_categories']) ? 1 : 0;
        $data['has_tags'] = isset($raw['has_tags']) ? 1 : 0;

        foreach (['description', 'icon'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $errors ? ['errors' => $errors] : $data;
    }

    private function rawInput(): array
    {
        return (array) Flight::request()->data->getData();
    }

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

    private function renderView(string $view, array $data = []): void
    {
        if (strpos($view, '.blade') !== false || file_exists(dirname(__DIR__, 3) . '/views/' . str_replace('.', '/', $view) . '.blade.php')) {
            echo Flight::get('blade')->render($view, $data);
        } else {
            Flight::view()->render($view, $data);
        }
    }
}