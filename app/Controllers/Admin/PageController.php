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
        'type', 'layout',
        'title', 'slug', 'content_json',
        'featured_image',
        'seo_title', 'seo_description', 'seo_keywords', 'seo_image',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image',
        'canonical_url', 'robots', 'twitter_card',
        'status', 'published_at',
    ];

    /**
     * Fields that must be present and non-empty on create.
     */
    private const REQUIRED = ['title'];

    /** Top-level paths a page slug must never shadow. */
    private const RESERVED_SLUGS = [
        'admin', 'install', 'api', 'storage', 'themes', 'uploads',
        'assets', 'contact', 'search', 'sitemap', 'robots', 'favicon',
        'blog',
    ];

    /**
     * The only page statuses the application understands: the admin form
     * offers draft/published, and scheduling is a published page with a
     * future published_at. Anything else (a typo, hand-crafted input)
     * silently makes a page permanently invisible — visible() matches
     * 'published' exactly.
     */
    private const ALLOWED_STATUSES = ['draft', 'published'];

    /**
     * Core page types referenced by code (sitemap, archives, product
     * galleries). ContentType slugs are allowed in addition and are
     * validated against the content_types table at runtime.
     */
    private const CORE_PAGE_TYPES = ['page', 'post', 'feature', 'product', 'products'];

    /**
     * Fields whose empty string should be stored as NULL.
     * (i.e. optional SEO fields — avoids storing "" in the DB)
     */
    private const NULLABLE = [
        'featured_image',
        'seo_title', 'seo_description', 'seo_keywords', 'seo_image',
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
        $search = $_GET['q'] ?? null;

        $query = Page::with(['categories', 'tags']);
        if (!empty($selectedType) && $selectedType !== 'all') {
            $query = $query->type($selectedType);
        }

        if (!empty($search)) {
            $query = $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('content_json', 'like', "%{$search}%");
            });
        }

        // paginate results (20 per page) without relying on Illuminate paginator being bootstrapped
        $perPage = 20;
        $pageNum = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        // total before limit
        $total = (int) $query->count();

        $offset = ($pageNum - 1) * $perPage;
        $pages = $query->orderBy('title')->skip($offset)->take($perPage)->get();

        $lastPage = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        $paginator = [
            'current' => $pageNum,
            'last' => $lastPage,
            'total' => $total,
            'perPage' => $perPage,
        ];

        $this->renderView('admin.pages.index', compact('pages', 'types', 'selectedType', 'search', 'paginator'));
    }

    public function create(): void
    {
        $this->renderView('admin.pages.create', $this->formData());
    }

    public function store(): void
    {
        $input = $this->getValidatedInput();
        $raw = $this->rawInput();

        if (isset($input['errors'])) {
            // Re-render create form with validation errors and old input
            $this->renderView('admin.pages.create', array_merge(
                $this->formData(),
                ['errors' => $input['errors'], 'old' => $this->rawInput()],
            ));
            return;
        }

        try {
            $input['featured_image'] = $this->resolveFeaturedImageValue($input, $raw);
            $input['slug'] = unique_slug(
                $input['slug'] ?: $input['title']
            );

            // getValidatedInput() already normalized a submitted value;
            // the fallbacks only run when the field was absent/blank.
            $input['published_at'] = $input['published_at']
                ?? ((($input['status'] ?? 'published') === 'published') ? \Illuminate\Support\Carbon::now() : null);

            $page = Page::create($input);
            $this->syncRelations($page);
            \Event::dispatch('page.created', $page);

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
        $raw = $this->rawInput();

        if (isset($input['errors'])) {
            $this->renderView('admin.pages.edit', array_merge(
                $this->formData(),
                ['errors' => $input['errors'], 'old' => $this->rawInput(), 'page' => $page],
            ));
            return;
        }

        try {
            $input['featured_image'] = $this->resolveFeaturedImageValue($input, $raw);
            $input['slug'] = unique_slug(
                $input['slug'] ?: $input['title'],
                $id
            );

            // A submitted value was normalized by getValidatedInput();
            // never silently reset it to now(). The fallbacks only run
            // when the field was absent/blank.
            $input['published_at'] = $input['published_at']
                ?? ((($input['status'] ?? 'published') === 'published') ? \Illuminate\Support\Carbon::now() : null);

            // A changed publish date or status re-arms the scheduled-
            // publishing notifier so scripts/publish-scheduled.php fires
            // page.published (and its cache invalidation) again. The
            // column is not mass-assignable, so it is set on the model.
            $notifierChanged = ($input['published_at'] ?? null) !== $page->getRawOriginal('published_at')
                || ($input['status'] ?? null) !== $page->getRawOriginal('status');

            $page->update($input);

            if ($notifierChanged) {
                $page->published_notified_at = null;
                $page->save();
            }

            $this->syncRelations($page);
            \Event::dispatch('page.updated', $page);

            \Flight::redirect('/admin/pages?saved=1');
        } catch (Exception $e) {
            $this->renderView('admin.pages.edit', array_merge(
                $this->formData(),
                ['errors' => ['An unexpected error occurred: ' . $e->getMessage()], 'old' => $this->rawInput(), 'page' => $page],
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
            \Event::dispatch('page.deleted', $page);

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
            'layouts'    => \App\Core\Theme::layouts(),
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

        // Slugs that would collide with framework/system paths can never
        // be reached as public pages anyway — reject them up front.
        if (!empty($data['slug']) && in_array($data['slug'], self::RESERVED_SLUGS, true)) {
            $errors[] = "'{$data['slug']}' is a reserved path and cannot be used as a page slug.";
        }

        // 5.5 Status and type allowlists: only values the application
        // actually understands. A mistyped status silently makes a page
        // permanently invisible; a mistyped type drops it from every
        // listing. Empty/absent values are left untouched so legacy rows
        // with an empty type keep editing cleanly.
        if (isset($data['status']) && $data['status'] !== ''
            && !in_array($data['status'], self::ALLOWED_STATUSES, true)) {
            $errors[] = "'status' must be either 'draft' or 'published'.";
        }

        if (isset($data['type']) && $data['type'] !== ''
            && !in_array($data['type'], self::allowedPageTypes(), true)) {
            $errors[] = "'type' is not a known content type.";
        }

        if (array_key_exists('layout', $data)) {
            if ($data['layout'] === '') {
                $data['layout'] = 'default';
            } elseif (!in_array($data['layout'], \App\Core\Theme::layouts(), true)) {
                $errors[] = "'layout' is not a layout provided by the active theme.";
            }
        }

        // 7. Publish date: strict parse instead of strtotime(), which
        // silently turns garbage into 1970-01-01. A present-but-invalid
        // value is a validation error; a valid one is normalized to the
        // database format here, interpreted in the application timezone
        // (Bootstrap::applyTimezone sets PHP's default timezone).
        if (isset($raw['published_at']) && trim((string) $raw['published_at']) !== '') {
            $parsed = self::parsePublishedAt((string) $raw['published_at']);
            if ($parsed === null) {
                $errors[] = "'published_at' is not a valid date and time (expected YYYY-MM-DD HH:MM).";
            } else {
                $data['published_at'] = $parsed;
            }
        }

        if (empty($errors)) {
            $contentJson = $this->mergeCustomFieldsIntoContentJson(
                (string) ($data['content_json'] ?? '[]'),
                (array) ($raw['custom_fields'] ?? [])
            );

            // Strip unsafe HTML from block content before it is stored.
            $data['content_json'] = \App\Core\HtmlSanitizer::sanitizeBlocksJson($contentJson);

            // Per-type validation the sanitizer cannot judge: video_embed
            // URLs must come from the allowlisted hosts.
            foreach (self::validateBlocks(json_decode((string) $data['content_json'], true) ?: []) as $blockError) {
                $errors[] = $blockError;
            }
        }

        return $errors ? ['errors' => $errors] : $data;
    }

    /**
     * Every page type a submission may use: the core types referenced by
     * application code plus the slugs registered in the content_types
     * table. Empty string/null stay unvalidated so legacy rows with an
     * empty type keep editing cleanly.
     *
     * @return string[]
     */
    private static function allowedPageTypes(): array
    {
        $types = self::CORE_PAGE_TYPES;
        try {
            $types = array_merge($types, \App\Models\ContentType::query()->pluck('slug')->all());
        } catch (\Throwable $e) {
            // content_types table unavailable: core types still apply.
        }

        return array_values(array_unique($types));
    }

    /**
     * Strictly parse an admin-submitted publish date (the form posts
     * datetime-local values, 'YYYY-MM-DDTHH:MM') in the application
     * timezone. Returns the database format, or null when the value is
     * not a real date/time — never a silent 1970.
     */
    public static function parsePublishedAt(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $value);
            if ($dt === false) {
                continue;
            }
            $lastErrors = \DateTimeImmutable::getLastErrors();
            $clean = is_array($lastErrors)
                ? ((int) ($lastErrors['warning_count'] ?? 0)) === 0 && ((int) ($lastErrors['error_count'] ?? 0)) === 0
                : true;
            if ($clean) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /**
     * Block-type-specific validation, run after the sanitizer. The
     * sanitizer only handles html/text; video_embed URLs must point at
     * the allowlisted providers (YouTube/Vimeo) so arbitrary third-party
     * scripts cannot be embedded.
     *
     * @param array $blocks decoded content_json
     * @return string[] validation errors
     */
    private static function validateBlocks(array $blocks): array
    {
        $errors = [];
        foreach ($blocks as $index => $block) {
            if (!is_array($block) || ($block['type'] ?? '') !== 'video_embed') {
                continue;
            }
            $url = trim((string) ($block['url'] ?? ''));
            if ($url === '') {
                $errors[] = 'Block ' . ($index + 1) . ' (video): a video URL is required.';
                continue;
            }
            if (!preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,20})#i', $url)
                && !preg_match('#vimeo\.com/(\d{6,12})#i', $url)) {
                $errors[] = 'Block ' . ($index + 1) . ' (video): only YouTube and Vimeo URLs are supported.';
            }
        }

        return $errors;
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

        // JSON_UNESCAPED_SLASHES is deliberately NOT used: escaped forward
        // slashes keep "</script>" sequences from surviving into output.
        return json_encode($blocks, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * Raw POST input via Flight (avoids direct $_POST access in business logic).
     */
    private function rawInput(): array
    {
        $flightData = (array) \Flight::request()->data->getData();

        // Some environments/middlewares can cause Flight's parsed body to miss
        // fields that PHP still provides via $_POST. Merge as a safe fallback.
        if (!empty($_POST) && is_array($_POST)) {
            return array_merge($flightData, $_POST);
        }

        return $flightData;
    }

    private function resolveFeaturedImageValue(array $validatedInput, array $rawInput)
    {
        $value = null;

        if (array_key_exists('featured_image', $rawInput)) {
            $value = $rawInput['featured_image'];
        } elseif (array_key_exists('featured_image_persist', $rawInput)) {
            $value = $rawInput['featured_image_persist'];
        } elseif (array_key_exists('featured_image', $validatedInput)) {
            $value = $validatedInput['featured_image'];
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
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
