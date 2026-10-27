<?php

namespace App\Controllers\Admin;

use App\Core\Theme;
use Illuminate\Database\Capsule\Manager as Capsule;

class ThemeController
{
    public static function index()
    {
        $themes = self::availableThemes();
        $activeTheme = Theme::active();

        // Read current show_theme_credit setting so theme settings can control footer credit
        $showThemeCredit = '1';
        try {
            $val = Capsule::table('settings')->where('key', 'show_theme_credit')->value('value');
            if ($val !== null) {
                $showThemeCredit = (string) $val;
            }
        } catch (\Exception $e) {
            // If settings table missing or other db error, default to '1'
        }

        echo \Flight::get('blade')->render('admin.themes.index', compact('themes', 'activeTheme', 'showThemeCredit'));
    }

    public static function update()
    {
        $data = \Flight::request()->data->getData();
        $theme = trim((string) ($data['theme'] ?? ''));

        if (!self::themeExists($theme)) {
            \Flight::redirect('/admin/themes?status=invalid');
            return;
        }

        if (!Capsule::schema()->hasTable('settings')) {
            \Flight::redirect('/admin/themes?status=settings-missing');
            return;
        }

        Capsule::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            ['value' => $theme]
        );

        // Save show_theme_credit if provided in the theme settings form
        $showCredit = !empty($data['show_theme_credit']) && $data['show_theme_credit'] == '1' ? '1' : '0';
        try {
            Capsule::table('settings')->updateOrInsert(['key' => 'show_theme_credit'], ['value' => $showCredit]);
        } catch (\Exception $e) {
            // Ignore errors here; theme change already persisted above
        }

        setting_cache_clear();
        \App\Middleware\PageCache::clear();

        // Publish theme assets into public/themes so they are served
        // directly by the web server (PHP route stays as fallback).
        if (!Theme::publishAssets($theme)) {
            error_log('Failed to publish assets for theme: ' . $theme);
        }

        \Flight::redirect('/admin/themes?status=updated');
    }

    private static function availableThemes(): array
    {
        $themesDir = dirname(__DIR__, 3) . '/themes';

        if (!is_dir($themesDir)) {
            return [];
        }

        $themeFolders = glob($themesDir . '/*', GLOB_ONLYDIR) ?: [];
        $themes = [];

        foreach ($themeFolders as $folder) {
            $slug = basename($folder);
            $metaPath = $folder . '/theme.json';
            $meta = [];

            if (is_file($metaPath)) {
                $decoded = json_decode((string) file_get_contents($metaPath), true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }

            $themes[] = [
                'slug' => $slug,
                'name' => $meta['name'] ?? ucfirst($slug),
                'version' => $meta['version'] ?? '',
                'author' => $meta['author'] ?? '',
                'description' => $meta['description'] ?? '',
            ];
        }

        usort($themes, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $themes;
    }

    public static function showOptions()
    {
        $slug = \App\Core\Theme::active();
        $options = \App\Core\Theme::options($slug);
        echo \Flight::get('blade')->render('admin.themes.options', compact('slug', 'options'));
    }

    public static function saveOptions()
    {
        $slug = \App\Core\Theme::active();
        $data = \Flight::request()->data->getData();

        $values = [];
        foreach (\App\Core\Theme::options($slug) as $key => $option) {
            $values[$key] = (string) ($data[$key] ?? '');
        }

        $errors = \App\Core\Theme::validateOptions($values, $slug);
        if ($errors !== []) {
            \Flight::redirect('/admin/themes/options?error=' . urlencode(implode(' ', $errors)));
            return;
        }

        try {
            Capsule::table('settings')->updateOrInsert(
                ['key' => 'theme_options_' . $slug],
                ['value' => json_encode($values, JSON_UNESCAPED_UNICODE)]
            );
            setting_cache_clear();
            \App\Middleware\PageCache::clear();
            \Flight::redirect('/admin/themes/options?status=saved');
        } catch (\Throwable $e) {
            \Flight::redirect('/admin/themes/options?error=' . urlencode('Could not save options.'));
        }
    }

    private static function themeExists(string $slug): bool
    {
        // Same rule as Theme::normalizeThemeSlug: charset AND a real
        // directory directly under themes/ — '../app', '..' and 'a/b'
        // must never activate a theme outside themes/.
        return Theme::normalizeThemeSlug($slug) !== null;
    }
}
