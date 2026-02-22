<?php

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Template;
use App\Models\ContentType;
use Exception;

class PageController
{
    /**
     * The only fields ever written to the pages table.
     * Everything else in $_POST is silently ignored.
     */
    private const FILLABLE = [
        'type',
        'title', 'slug', 'content_json',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image',
        'canonical_url', 'robots', 'twitter_card',
    ];

    /**
     * Fields that must be present and non-empty on create.
     */
    private const REQUIRED = ['title'];

    /**
     * Fields whose empty string should be stored as NULL.
     * (i.e. optional SEO fields — avoids storing "" in the DB)
     */
    private const NULLABLE = [
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image',
        'canonical_url', 'robots', 'twitter_card',
    ];

    // -------------------------------------------------------------------------
    // Public Actions
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $types = ContentType::orderBy('name')->get();

        $selectedType = $_GET['type'] ?? 'all';

        $query = Page::with(['categories', 'tags']);
        if (!empty($selectedType) && $selectedType !== 'all') {
            $query = $query->type($selectedType);
        }

        $pages = $query->get();

        $this->renderView('admin.pages.index', compact('pages', 'types', 'selectedType'));
    }

    public function create(): void
    {
        $this->renderView('admin.pages.create', $this->formData());
    }

    public function store(): void
    {
        $input = $this->getValidatedInput();

        if (isset($input['errors'])) {
            // Re-render create form with validation errors and old input
            $this->renderView('admin.pages.create', array_merge(
                $this->formData(),
                ['errors' => $input['errors'], 'old' => $this->rawInput()],
            ));
            return;
        }

        try {
            $input['slug'] = unique_slug(
                $input['slug'] ?: $input['title']
            );

            $page = Page::create($input);
            $this->syncRelations($page);

            \Flight::redirect('/admin/pages?saved=1');
        } catch (Exception $e) {
            $this->renderView('admin.pages.create', array_merge(
                $this->formData(),
                ['errors' => ['An unexpected error occurred. Please try again.'], 'old' => $this->rawInput()],
            ));
        }
    }

    public function edit(int $id): void
    {
        $page = $this->findOrAbort($id);

        $this->renderView('admin.pages.edit', array_merge(
            $this->formData(),
            compact('page'),
        ));
    }

    public function update(int $id): void
    {


        $page  = $this->findOrAbort($id);
        $input = $this->getValidatedInput(isUpdate: true);

        if (isset($input['errors'])) {
            $this->renderView('admin.pages.edit', array_merge(
                $this->formData(),
                ['errors' => $input['errors'], 'old' => $this->rawInput(), 'page' => $page],
            ));
            return;
        }

        try {
            $input['slug'] = unique_slug(
                $input['slug'] ?: $input['title'],
                $id
            );

            $page->update($input);
            $this->syncRelations($page);

            \Flight::redirect('/admin/pages?saved=1');
        } catch (Exception $e) {
            $this->renderView('admin.pages.edit', array_merge(
                $this->formData(),
                ['errors' => ['An unexpected error occurred. Please try again.'], 'old' => $this->rawInput(), 'page' => $page],
            ));
        }
    }

    public function destroy(int $id): void
    {
        $page = $this->findOrAbort($id);

        try {
            $page->categories()->detach();
            $page->tags()->detach();
            $page->delete();

            \Flight::redirect('/admin/pages?deleted=1');
        } catch (Exception $e) {
            \Flight::redirect('/admin/pages?error=delete_failed');
        }
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Shared data needed by both create and edit forms.
     */
    private function formData(): array
    {
        $types = ContentType::with(['fields' => function ($query) {
            $query->orderBy('sort_order');
        }])->orderBy('name')->get();

        $contentTypeFieldsBySlug = [];
        foreach ($types as $type) {
            $contentTypeFieldsBySlug[$type->slug] = $type->fields->map(function ($field) {
                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'required' => (bool) $field->required,
                    'options' => $field->options,
                ];
            })->values()->all();
        }

        return [
            'templates'  => Template::all(),
            'categories' => Category::orderBy('name')->get(),
            'tags'       => Tag::orderBy('name')->get(),
            'types'      => $types,
            'contentTypeFieldsBySlug' => $contentTypeFieldsBySlug,
        ];
    }

    /**
     * Sync category and tag pivot relations from POST data.
     * IDs are cast to integers to prevent type-juggling attacks.
     */
    private function syncRelations(Page $page): void
    {
        $categoryIds = array_map('intval', (array) ($_POST['category_ids'] ?? []));
        $tagIds      = array_map('intval', (array) ($_POST['tag_ids']      ?? []));

        $page->categories()->sync($categoryIds);
        $page->tags()->sync($tagIds);
    }

    /**
     * Read raw POST, whitelist fields, sanitize, apply nullables, and validate.
     *
     * @param bool $isUpdate When true, required fields are not enforced (PATCH semantics).
     * @return array Clean data, or ['errors' => string[]] on failure.
     */
    private function getValidatedInput(bool $isUpdate = false): array
    {
        $raw = $this->rawInput();

        // 1. Whitelist
        $data = array_intersect_key($raw, array_flip(self::FILLABLE));

        // 2. Trim all strings
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        // 3. Null out empty optional fields (avoids storing "" in the DB)
        foreach (self::NULLABLE as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // 4. Validate required fields
        $errors = [];

        if (!$isUpdate) {
            foreach (self::REQUIRED as $field) {
                if (empty($data[$field])) {
                    $errors[] = "'{$field}' is required.";
                }
            }
        }

        // 5. Validate slug format if provided
        if (!empty($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = "'slug' may only contain lowercase letters, numbers, and hyphens.";
        }

        if (empty($errors)) {
            $data['content_json'] = $this->mergeCustomFieldsIntoContentJson(
                (string) ($data['content_json'] ?? '[]'),
                (array) ($raw['custom_fields'] ?? [])
            );
        }

        return $errors ? ['errors' => $errors] : $data;
    }

    private function mergeCustomFieldsIntoContentJson(string $contentJson, array $customFields): string
    {
        $blocks = json_decode($contentJson, true);
        if (!is_array($blocks)) {
            $blocks = [];
        }

        $filtered = [];
        foreach ($customFields as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            if (is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            }

            $filtered[$key] = is_string($value) ? trim($value) : (string) $value;
        }

        $metaBlockIndex = null;
        foreach ($blocks as $index => $block) {
            if (is_array($block) && (($block['type'] ?? '') === '__custom_fields')) {
                $metaBlockIndex = $index;
                break;
            }
        }

        if (!empty($filtered)) {
            $metaBlock = ['type' => '__custom_fields', 'fields' => $filtered];
            if ($metaBlockIndex === null) {
                $blocks[] = $metaBlock;
            } else {
                $blocks[$metaBlockIndex] = $metaBlock;
            }
        } elseif ($metaBlockIndex !== null) {
            unset($blocks[$metaBlockIndex]);
            $blocks = array_values($blocks);
        }

        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    /**
     * Raw POST input via Flight (avoids direct $_POST access in business logic).
     */
    private function rawInput(): array
    {
        return (array) \Flight::request()->data->getData();
    }

    /**
     * Find a page by ID or respond with 404 and halt execution.
     */
    private function findOrAbort(int $id): Page
    {
        $page = Page::find($id);

        if (!$page) {
            \Flight::response()->status(404);
            $this->renderView('admin.errors.404', ['message' => 'Page not found.']);
            exit;
        }

        return $page;
    }

    /**
     * Render a Blade view.
     */
    private function renderView(string $view, array $data = []): void
    {
        echo \Flight::get('blade')->render($view, $data);
    }
}