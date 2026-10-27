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
        return url('/themes/' . $theme . '/assets/' . ltrim($file, '/'));
    }

    /**
     * The theme's theme.json contents (statically cached per theme), or
     * an empty array when the file is missing or invalid.
     *
     * @return array<string, mixed>
     */
    public static function meta(?string $slug = null): array
    {
        static $cache = [];
        $slug = $slug ?: self::active();

        if (!array_key_exists($slug, $cache)) {
            $path = self::path($slug, 'theme.json');
            $meta = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
            $cache[$slug] = is_array($meta) ? $meta : [];
        }

        return $cache[$slug];
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

    public static function layouts(?string $theme = null): array
    {
        $dir = self::path($theme, 'views/templates');
        $files = is_dir($dir) ? (glob($dir . '/*.blade.php') ?: []) : [];

        $layouts = array_map(function ($file) {
            return basename($file, '.blade.php');
        }, $files);

        if (empty($layouts)) {
            $layouts = ['default'];
        }

        sort($layouts);
        return $layouts;
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

    /**
     * Publish a theme's assets into public/themes/<theme>/assets so the
     * web server serves them without booting the application. The PHP
     * route in routes/web.php remains the fallback.
     */
    public static function publishAssets(?string $theme = null): bool
    {
        $theme = $theme ?: self::active();
        $source = self::path($theme, 'assets');
        if (!is_dir($source)) {
            return false;
        }

        $targetDir = dirname(__DIR__, 2) . '/public/themes/' . $theme;
        $target = $targetDir . '/assets';

        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        if (is_link($target)) {
            // Directory symlinks on Windows must be removed with rmdir.
            @rmdir($target);
            @unlink($target);
        }
        if (is_dir($target)) {
            self::removeRecursive($target);
        } elseif (is_file($target)) {
            @unlink($target);
        }

        // Publish as a plain recursive copy: deterministic across
        // platforms (symlink() behaves unpredictably on Windows — it can
        // fail while still leaving dangling directory links behind).
        self::copyRecursive($source, $target);
        return is_dir($target);
    }

    private static function copyRecursive(string $src, string $dst): void
    {
        @mkdir($dst, 0775, true);

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            $target = $dst . '/' . $it->getSubPathName();
            if ($file->isDir()) {
                @mkdir($target, 0775, true);
            } else {
                $targetParent = dirname($target);
                if (!is_dir($targetParent)) {
                    @mkdir($targetParent, 0775, true);
                }
                @copy($file->getPathname(), $target);
            }
        }
    }

    private static function removeRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }

    /**
     * Validate a theme slug: ^[a-z0-9_-]+$ AND a real directory located
     * DIRECTLY under themes/ (realpath on both sides, so '..', '../app',
     * 'a/b' and symlinked aliases can never resolve outside themes/).
     * Public so ThemeController::themeExists applies the identical rule.
     */
    public static function normalizeThemeSlug($theme): ?string
    {
        $theme = trim((string) $theme);
        if ($theme === '' || !preg_match('/^[a-z0-9_-]+$/', $theme)) {
            return null;
        }

        $themesRoot = realpath(dirname(__DIR__, 2) . '/themes');
        if ($themesRoot === false) {
            return null;
        }

        $resolved = realpath($themesRoot . '/' . $theme);
        if ($resolved === false || dirname($resolved) !== $themesRoot || !is_dir($resolved)) {
            return null;
        }

        return $theme;
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
