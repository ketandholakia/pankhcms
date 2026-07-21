<?php
namespace App\Controllers\Admin;

use Illuminate\Database\Capsule\Manager as Capsule;

class PluginController
{
    private const PLUGIN_UPLOADS_ENV = 'ENABLE_PLUGIN_UPLOADS';

    protected static function ensurePluginsTable(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('plugins')) {
            return;
        }

        $schema->create('plugins', function ($t) {
            $t->increments('id');
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('version')->default('1.0.0');
            $t->integer('active')->default(0);
            $t->dateTime('installed_at')->nullable();
            $t->timestamps();
        });
    }

    public static function index()
    {
        self::ensurePluginsTable();

        $plugins = [];
        $pluginDir = __DIR__ . '/../../../plugins';
        foreach (glob($pluginDir . '/*', GLOB_ONLYDIR) as $dir) {
            $jsonFile = $dir . '/plugin.json';
            if (!file_exists($jsonFile)) continue;
            $meta = json_decode(file_get_contents($jsonFile), true);
            $db = Capsule::table('plugins')->where('slug', $meta['slug'])->first();
            $plugins[] = [
                'name' => $meta['name'],
                'slug' => $meta['slug'],
                'version' => $meta['version'],
                'description' => $meta['description'],
                'active' => $db ? $db->active : 0
            ];
        }
        $flash = $_SESSION['plugin_flash'] ?? null;
        unset($_SESSION['plugin_flash']);
        echo \Flight::get('blade')->render('admin.plugins.index', ['plugins' => $plugins, 'flash' => $flash]);
    }

    public static function toggle()
    {
        self::ensurePluginsTable();

        $slug = $_POST['slug'] ?? '';
        if (!$slug) {
            $_SESSION['plugin_flash'] = 'Invalid plugin request.';
            \Flight::redirect('/admin/plugins');
            return;
        }
        try {
            $plugin = Capsule::table('plugins')->where('slug', $slug)->first();
        } catch (\Throwable $e) {
            $plugin = null;
        }

        if ($plugin && $plugin->active) {
            \PluginManager::deactivate($slug);
        } else {
            \PluginManager::activate($slug);
        }
        $_SESSION['plugin_flash'] = 'Plugin status updated.';
        \Flight::redirect('/admin/plugins');
    }

    public static function upload()
    {
        self::ensurePluginsTable();

        if (!self::pluginUploadsEnabled()) {
            $_SESSION['plugin_flash'] = 'Plugin ZIP uploads are disabled. Deploy plugins via version control or CLI.';
            \Flight::redirect('/admin/plugins');
            return;
        }

        if (!isset($_FILES['plugin_zip']) || $_FILES['plugin_zip']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['plugin_flash'] = 'Upload failed.';
            \Flight::redirect('/admin/plugins');
            return;
        }
        $zipPath = $_FILES['plugin_zip']['tmp_name'];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $pluginDir = __DIR__ . '/../../../plugins';
            $extractTo = $pluginDir;
            $zip->extractTo($extractTo);
            $zip->close();
            $_SESSION['plugin_flash'] = 'Plugin uploaded.';
        } else {
            $_SESSION['plugin_flash'] = 'Invalid zip file.';
        }
        \Flight::redirect('/admin/plugins');
    }

    private static function pluginUploadsEnabled(): bool
    {
        $value = $_ENV[self::PLUGIN_UPLOADS_ENV]
            ?? $_SERVER[self::PLUGIN_UPLOADS_ENV]
            ?? getenv(self::PLUGIN_UPLOADS_ENV)
            ?? '';

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function uninstall()
    {
        self::ensurePluginsTable();

        $slug = $_POST['slug'] ?? '';
        if (!$slug) {
            $_SESSION['plugin_flash'] = 'Invalid plugin request.';
            \Flight::redirect('/admin/plugins');
            return;
        }
        $result = \PluginManager::uninstall($slug);
        if ($result) {
            $_SESSION['plugin_flash'] = 'Plugin uninstalled.';
        } else {
            $_SESSION['plugin_flash'] = 'Plugin not found or uninstall failed.';
        }
        \Flight::redirect('/admin/plugins');
    }
}
