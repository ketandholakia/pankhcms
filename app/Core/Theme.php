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

    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/themes/' . self::active();
    }

    public static function viewPath(): string
    {
        return self::path() . '/views';
    }

    public static function asset(string $file): string
    {
        return '/themes/' . self::active() . '/assets/' . ltrim($file, '/');
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
