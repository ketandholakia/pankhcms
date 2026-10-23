<?php
namespace App\Controllers\Admin;

use Illuminate\Database\Capsule\Manager as Capsule;

class PluginController
{
    private const PLUGIN_UPLOADS_ENV = 'ENABLE_PLUGIN_UPLOADS';

    public static function index()
    {
        $plugins = [];
        $pluginDir = __DIR__ . '/../../../plugins';
        // One query for every plugin row; the old loop issued one
        // query per discovered plugin directory.
        $dbRows = Capsule::table('plugins')->get()->keyBy('slug');
        foreach (glob($pluginDir . '/*', GLOB_ONLYDIR) as $dir) {
            $jsonFile = $dir . '/plugin.json';
            if (!file_exists($jsonFile)) continue;
            $meta = json_decode(file_get_contents($jsonFile), true);
            $db = $dbRows[$meta['slug']] ?? null;
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
        $slug = $_POST['slug'] ?? '';
        if (!\PluginManager::isValidSlug((string) $slug)) {
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
        try {
            $slug = \PluginManager::installFromZip($_FILES['plugin_zip']['tmp_name']);
            $_SESSION['plugin_flash'] = 'Plugin uploaded: ' . $slug;
        } catch (\RuntimeException $e) {
            $_SESSION['plugin_flash'] = 'Plugin upload rejected: ' . $e->getMessage();
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
        $slug = $_POST['slug'] ?? '';
        if (!\PluginManager::isValidSlug((string) $slug)) {
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
