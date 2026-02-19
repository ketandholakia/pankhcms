<?php

namespace App\Controllers\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class SeoController
{
    /**
     * Single source of truth: maps form field name → settings table key.
     *
     * Previously these were two separate hardcoded lists maintained in sync
     * across getDefaults() and update() independently. Any rename touched
     * at least 4 lines in 2 methods. Now it's one line in one place.
     */
    private const FIELDS = [
        'default_title'       => 'seo_default_title',
        'default_description' => 'seo_default_description',
        'default_keywords'    => 'seo_default_keywords',
        'og_title_default'    => 'og_title_default',
        'og_description_default' => 'og_description_default',
        'og_image_default'    => 'og_image_default',
        'canonical_base'      => 'canonical_base',
        'robots_default'      => 'robots_default',
        'twitter_card'        => 'twitter_card',
        'twitter_site'        => 'twitter_site',
    ];

    private const RULES = [
        'default_title'          => ['max' => 70],   // Google truncates ~57–70 chars
        'default_description'    => ['max' => 160],  // Standard meta description limit
        'default_keywords'       => ['max' => 255],
        'og_title_default'       => ['max' => 95],
        'og_description_default' => ['max' => 200],
        'og_image_default'       => ['max' => 2048, 'url' => true],
        'canonical_base'         => ['max' => 2048, 'url' => true],
        'robots_default'         => ['max' => 100, 'allowed' => [
            '', 'index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow',
        ]],
        'twitter_card'           => ['max' => 50, 'allowed' => [
            '', 'summary', 'summary_large_image', 'app', 'player',
        ]],
        'twitter_site'           => ['max' => 50],
    ];

    // -------------------------------------------------------------------------
    // Public Actions
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $defaults = $this->loadSettings();

        $this->renderView('admin.settings.seo', compact('defaults'));
    }

    public function update(): void
    {
        if (!$this->settingsTableExists()) {
            \Flight::redirect('/admin/settings/seo?status=settings-missing');
            return;
        }

        $input = $this->getValidatedInput();

        if (isset($input['errors'])) {
            $defaults = $this->loadSettings();
            $this->renderView('admin.settings.seo', [
                'defaults' => array_merge($defaults, $this->rawInput()),
                'errors'   => $input['errors'],
            ]);
            return;
        }

        try {
            $this->persistSettings($input);
            \Flight::redirect('/admin/settings/seo?status=updated');
        } catch (Exception $e) {
            $defaults = $this->loadSettings();
            $this->renderView('admin.settings.seo', [
                'defaults' => array_merge($defaults, $input),
                'errors'   => ['Failed to save settings. Please try again.'],
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Load all SEO settings from the DB and map them back to form field names.
     * Returns safe empty-string defaults if the table is missing or a query fails.
     */
    private function loadSettings(): array
    {
        $defaults = array_fill_keys(array_keys(self::FIELDS), '');

        if (!$this->settingsTableExists()) {
            return $defaults;
        }

        try {
            $rows = Capsule::table('settings')
                ->whereIn('key', array_values(self::FIELDS))
                ->pluck('value', 'key')
                ->toArray();

            // Map DB key → form field name
            foreach (self::FIELDS as $formField => $dbKey) {
                $defaults[$formField] = (string) ($rows[$dbKey] ?? '');
            }
        } catch (\Throwable $e) {
            // Return empty defaults rather than crashing the settings page
        }

        return $defaults;
    }

    /**
     * Write all form fields to the settings table in a single transaction.
     * One updateOrInsert per key — wrapped so all succeed or all fail together.
     */
    private function persistSettings(array $input): void
    {
        Capsule::transaction(function () use ($input) {
            foreach (self::FIELDS as $formField => $dbKey) {
                Capsule::table('settings')->updateOrInsert(
                    ['key'   => $dbKey],
                    ['value' => $input[$formField] ?? ''],
                );
            }
        });
    }

    /**
     * Whitelist, sanitize, and validate POST input against RULES.
     *
     * @return array Clean data keyed by form field name, or ['errors' => string[]].
     */
    private function getValidatedInput(): array
    {
        $raw    = $this->rawInput();
        $data   = array_intersect_key($raw, self::FIELDS);   // whitelist to known form fields
        $errors = [];

        foreach (self::RULES as $field => $rules) {
            $value = trim((string) ($data[$field] ?? ''));
            $data[$field] = $value;

            if (isset($rules['max']) && strlen($value) > $rules['max']) {
                $errors[] = "'{$field}' must not exceed {$rules['max']} characters.";
            }

            if (isset($rules['allowed']) && !in_array($value, $rules['allowed'], strict: true)) {
                $errors[] = "'{$field}' contains an invalid value.";
            }

            if (!empty($rules['url']) && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[] = "'{$field}' must be a valid URL.";
            }
        }

        return $errors ? ['errors' => $errors] : $data;
    }

    /**
     * Check if the settings table exists, cached per request.
     */
    private function settingsTableExists(): bool
    {
        static $exists = null;
        return $exists ??= Capsule::schema()->hasTable('settings');
    }

    /**
     * Return raw POST data via Flight's request object.
     */
    private function rawInput(): array
    {
        return (array) \Flight::request()->data->getData();
    }

    /**
     * Render a Blade view.
     */
    private function renderView(string $view, array $data = []): void
    {
        echo \Flight::get('blade')->render($view, $data);
    }
}