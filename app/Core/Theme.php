<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Theme
{
    public static function active(): string
    {
        $candidates = [
            self::activeFromDatabase(),
            $_ENV['ACTIVE_THEME'] ?? null,
            self::default(),
        ];

        foreach ($candidates as $candidate) {
            $theme = self::normalizeThemeSlug($candidate);
            if ($theme !== null) {
                return $theme;
            }
        }

        return 'default';
    }

    public static function default(): string
    {
        $configured = self::normalizeThemeSlug($_ENV['DEFAULT_THEME'] ?? null);
        if ($configured !== null) {
            return $configured;
        }

        $starter = self::normalizeThemeSlug('pankhcmsstarter');
        if ($starter !== null) {
            return $starter;
        }

        $available = self::availableThemes();
        if (!empty($available)) {
            return $available[0];
        }

        return 'default';
    }

    public static function path(?string $theme = null, string $sub = ''): string
    {
        $theme = $theme ?: self::active();
        $base = dirname(__DIR__, 2) . '/themes/' . $theme;
        return $sub !== '' ? rtrim($base, '/') . '/' . ltrim($sub, '/') : $base;
    }

    public static function viewPath(?string $theme = null): string
    {
        return self::path($theme, 'views');
    }

    public static function asset(string $file, ?string $theme = null): string
    {
        $theme = $theme ?: self::active();
        return '/themes/' . $theme . '/assets/' . ltrim($file, '/');
    }

    /**
     * Convert a Blade view name (dot notation) to an absolute blade file path
     * within the theme.
     */
    public static function viewFile(string $view, ?string $theme = null): string
    {
        $relative = str_replace('.', '/', $view) . '.blade.php';
        return self::viewPath($theme) . '/' . $relative;
    }

    public static function viewExists(string $view, ?string $theme = null): bool
    {
        return is_file(self::viewFile($view, $theme));
    }

    private static function activeFromDatabase(): ?string
    {
        try {
            if (!Capsule::schema()->hasTable('settings')) {
                return null;
            }

            $value = Capsule::table('settings')->where('key', 'active_theme')->value('value');
            return $value ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function normalizeThemeSlug($theme): ?string
    {
        $theme = trim((string) $theme);
        if ($theme === '') {
            return null;
        }

        return is_dir(self::path($theme)) ? $theme : null;
    }

    private static function availableThemes(): array
    {
        $themesDir = dirname(__DIR__, 2) . '/themes';
        if (!is_dir($themesDir)) {
            return [];
        }

        $directories = glob($themesDir . '/*', GLOB_ONLYDIR) ?: [];
        $themes = array_map('basename', $directories);
        sort($themes);

        return $themes;
    }
}
