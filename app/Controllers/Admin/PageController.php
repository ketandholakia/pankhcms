<?php

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Template;
use Exception;

class PageController
{
    /**
     * The only fields ever written to the pages table.
     * Everything else in $_POST is silently ignored.
     */
    private const FILLABLE = [
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
        $pages = Page::with(['categories', 'tags'])->get();

        $this->renderView('admin.pages.index', compact('pages'));
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
        return [
            'templates'  => Template::all(),
            'categories' => Category::orderBy('name')->get(),
            'tags'       => Tag::orderBy('name')->get(),
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

        return $errors ? ['errors' => $errors] : $data;
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