<?php

class PluginManager
{
    private static $instances = [];

    public static function getPluginDir(): string
    {
        return __DIR__ . '/../../plugins';
    }

    /**
     * Slugs are used as directory names; only allow a safe subset.
     */
    public static function isValidSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z0-9_-]+$/', $slug) === 1;
    }

    /**
     * Resolve the real directory for a slug, refusing anything that is not
     * a discovered plugin or that resolves outside the plugins directory.
     */
    public static function getPluginDirForSlug(string $slug): ?string
    {
        if (!self::isValidSlug($slug)) {
            return null;
        }

        $all = self::discoverAll();
        if (!isset($all[$slug]['__dir'])) {
            return null;
        }

        $realTarget = realpath($all[$slug]['__dir']);
        $realRoot = realpath(self::getPluginDir());
        if ($realTarget === false || $realRoot === false) {
            return null;
        }

        if ($realTarget !== $realRoot && !str_starts_with($realTarget . DIRECTORY_SEPARATOR, $realRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realTarget;
    }

    public static function discoverAll(): array
    {
        $pluginDir = self::getPluginDir();
        $found = [];
        foreach (glob($pluginDir . '/*', GLOB_ONLYDIR) as $dir) {
            $jsonFile = $dir . '/plugin.json';
            if (!file_exists($jsonFile)) continue;
            $meta = json_decode(file_get_contents($jsonFile), true);
            $meta['__dir'] = $dir;
            $found[$meta['slug']] = $meta;
        }
        return $found;
    }

    public static function getActivePlugins(): array
    {
        try {
            if (class_exists('Illuminate\\Database\\Capsule\\Manager') && \Illuminate\Database\Capsule\Manager::schema()->hasTable('plugins')) {
                return \Illuminate\Database\Capsule\Manager::table('plugins')->where('active', 1)->pluck('slug')->toArray();
            }
        } catch (\Throwable $e) {
        }
        return array_keys(self::discoverAll());
    }

    public static function instantiate(array $meta)
    {
        $main = ($meta['__dir'] ?? self::getPluginDir() . '/' . $meta['slug']) . '/' . ($meta['main'] ?? 'Plugin.php');
        if (!file_exists($main)) return null;
        require_once $main;
        $class = str_replace(' ', '', $meta['name']) . 'Plugin';
        if (!class_exists($class)) return null;
        $instance = new $class($meta, $meta['__dir'] ?? '');
        self::$instances[$meta['slug']] = $instance;
        return $instance;
    }

    public static function boot()
    {
        $active = self::getActivePlugins();
        $all = self::discoverAll();
        foreach ($active as $slug) {
            if (!isset($all[$slug])) continue;
            $meta = $all[$slug];
            try {
                $inst = self::instantiate($meta);
                if ($inst) {
                    if (method_exists($inst, 'register')) $inst->register();
                    if (method_exists($inst, 'boot')) $inst->boot();
                }
            } catch (\Throwable $e) {
                error_log("Plugin [$slug] failed to boot: " . $e->getMessage());
            }
        }
    }

    public static function activate(string $slug)
    {
        if (!self::isValidSlug($slug)) return false;
        $all = self::discoverAll();
        if (!isset($all[$slug])) return false;
        $meta = $all[$slug];
        $inst = self::instantiate($meta);
        if ($inst && method_exists($inst, 'activate')) {
            $inst->activate();
        }
        try {
            if (class_exists('Illuminate\\Database\\Capsule\\Manager') && \Illuminate\Database\Capsule\Manager::schema()->hasTable('plugins')) {
                \Illuminate\Database\Capsule\Manager::table('plugins')->updateOrInsert(['slug' => $slug], [
                    'name' => $meta['name'] ?? $slug,
                    'version' => $meta['version'] ?? '0.0.0',
                    'active' => 1,
                    'installed_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Throwable $e) {}
        return true;
    }

    public static function deactivate(string $slug)
    {
        if (!self::isValidSlug($slug)) return false;
        if (isset(self::$instances[$slug]) && method_exists(self::$instances[$slug], 'deactivate')) {
            self::$instances[$slug]->deactivate();
        }
        try {
            if (class_exists('Illuminate\\Database\\Capsule\\Manager') && \Illuminate\Database\Capsule\Manager::schema()->hasTable('plugins')) {
                \Illuminate\Database\Capsule\Manager::table('plugins')->where('slug', $slug)->update(['active' => 0]);
            }
        } catch (\Throwable $e) {}
        return true;
    }

    public static function uninstall(string $slug)
    {
        if (!self::isValidSlug($slug)) return false;

        $pluginDir = self::getPluginDirForSlug($slug);
        if ($pluginDir === null) return false;

        if (isset(self::$instances[$slug]) && method_exists(self::$instances[$slug], 'uninstall')) {
            self::$instances[$slug]->uninstall();
        }

        if (is_dir($pluginDir)) {
            $it = new RecursiveDirectoryIterator($pluginDir, FilesystemIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) @rmdir($file->getRealPath()); else @unlink($file->getRealPath());
            }
            @rmdir($pluginDir);
        }
        try {
            if (class_exists('Illuminate\\Database\\Capsule\\Manager') && \Illuminate\Database\Capsule\Manager::schema()->hasTable('plugins')) {
                \Illuminate\Database\Capsule\Manager::table('plugins')->where('slug', $slug)->delete();
            }
        } catch (\Throwable $e) {}
        return true;
    }

    /**
     * Install a plugin from a ZIP archive. The archive is fully validated
     * before anything is extracted:
     *  - entry names must be relative (no absolute paths, no "..", no "\")
     *  - a plugin.json manifest is required
     *  - the manifest slug must be a valid slug
     *  - the target plugins/<slug> directory must not already exist
     * Files are extracted to a temp directory and moved into place, so an
     * invalid archive leaves the plugins directory untouched.
     *
     * @return string The installed plugin slug.
     * @throws \RuntimeException on any validation failure.
     */
    public static function installFromZip(string $zipPath): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('The PHP zip extension is required for plugin uploads.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Invalid zip file.');
        }

        $tmpDir = null;
        try {
            $numFiles = $zip->numFiles;
            if ($numFiles < 1) {
                throw new \RuntimeException('The zip archive is empty.');
            }

            for ($i = 0; $i < $numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === '') {
                    throw new \RuntimeException('The zip archive contains an unnamed entry.');
                }
                if (str_contains($name, '\\')) {
                    throw new \RuntimeException('Invalid entry name in zip archive.');
                }
                if (str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name) === 1) {
                    throw new \RuntimeException('Absolute paths are not allowed in plugin zips.');
                }
                foreach (explode('/', $name) as $part) {
                    if ($part === '..') {
                        throw new \RuntimeException('Path traversal is not allowed in plugin zips.');
                    }
                }
            }

            $tmpDir = sys_get_temp_dir() . '/pankhcms_plugin_' . uniqid();
            if (!mkdir($tmpDir, 0755, true)) {
                throw new \RuntimeException('Failed to create temporary directory.');
            }
            if (!$zip->extractTo($tmpDir)) {
                throw new \RuntimeException('Failed to extract the zip archive.');
            }

            // The plugin root is the temp dir itself or its single subdir.
            $entries = array_values(array_filter(scandir($tmpDir) ?: [], static fn ($e) => $e !== '.' && $e !== '..'));
            if (is_file($tmpDir . '/plugin.json')) {
                $pluginRoot = $tmpDir;
            } elseif (count($entries) === 1 && is_dir($tmpDir . '/' . $entries[0]) && is_file($tmpDir . '/' . $entries[0] . '/plugin.json')) {
                $pluginRoot = $tmpDir . '/' . $entries[0];
            } else {
                throw new \RuntimeException('The zip archive must contain a plugin.json manifest.');
            }

            $meta = json_decode((string) file_get_contents($pluginRoot . '/plugin.json'), true);
            $slug = is_array($meta) ? (string) ($meta['slug'] ?? '') : '';
            if (!self::isValidSlug($slug)) {
                throw new \RuntimeException('The plugin manifest has a missing or invalid slug.');
            }

            $target = realpath(self::getPluginDir()) . DIRECTORY_SEPARATOR . $slug;
            if (file_exists($target)) {
                throw new \RuntimeException('A plugin directory with this slug already exists.');
            }

            if (!@rename($pluginRoot, $target)) {
                throw new \RuntimeException('Failed to move the plugin into place.');
            }

            return $slug;
        } finally {
            $zip->close();
            if ($tmpDir !== null && is_dir($tmpDir)) {
                self::deleteDirectory($tmpDir);
            }
        }
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }

    public static function instances(): array
    {
        return self::$instances;
    }
}
