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

        echo \Flight::get('blade')->render('admin.themes.index', compact('themes', 'activeTheme'));
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

        // Automatically create symlink for theme assets
        $publicThemesDir = dirname(__DIR__, 3) . '/public/themes';
        $themeAssetsSource = dirname(__DIR__, 3) . '/themes/' . $theme . '/assets';
        $themeAssetsTargetDir = $publicThemesDir . '/' . $theme;
        $themeAssetsTarget = $themeAssetsTargetDir . '/assets';

        // Ensure public/themes/[theme] directory exists
        if (!is_dir($themeAssetsTargetDir)) {
            @mkdir($themeAssetsTargetDir, 0777, true);
        }

        // Remove existing symlink or directory if present
        if (is_link($themeAssetsTarget) || is_dir($themeAssetsTarget)) {
            @unlink($themeAssetsTarget);
        }

        // Create symlink if source exists and symlink() is available
        if (is_dir($themeAssetsSource)) {
            if (function_exists('symlink')) {
                $result = @\symlink($themeAssetsSource, $themeAssetsTarget);
                if ($result === false) {
                    $error = error_get_last();
                    error_log('symlink() failed: ' . print_r($error, true) . " | Source: $themeAssetsSource | Target: $themeAssetsTarget");
                }
            } else {
                error_log('symlink() is not available in this PHP environment. Source: ' . $themeAssetsSource . ' Target: ' . $themeAssetsTarget);
            }
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

    private static function themeExists(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }

        $path = dirname(__DIR__, 3) . '/themes/' . $slug;
        return is_dir($path);
    }
}
