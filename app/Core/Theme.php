<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Theme
{
    public static function active(): string
    {
        $dbTheme = self::activeFromDatabase();

        if (!empty($dbTheme)) {
            return $dbTheme;
        }

        return $_ENV['ACTIVE_THEME'] ?? 'default';
    }

    public static function default(): string
    {
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
}
