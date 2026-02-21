    public static function update()
    {
        if (!\Illuminate\Database\Capsule\Manager::schema()->hasTable('settings')) {
            \Flight::redirect('/admin/settings?status=settings-missing');
            return;
        }

        $data = \Flight::request()->data->getData();
        $fields = [
            'site_name', 'site_tagline', 'site_url', 'admin_email',
            'default_language', 'timezone', 'date_format', 'time_format'
        ];

        foreach ($fields as $key) {
            $value = $data[$key] ?? '';
            \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        // Handle logo upload
        if (!empty($_FILES['logo_upload']['tmp_name']) && is_uploaded_file($_FILES['logo_upload']['tmp_name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/media/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION);
            $logoPath = '/uploads/media/logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo_upload']['tmp_name'], __DIR__ . '/../../../public' . $logoPath);
            \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'logo_path'], ['value' => $logoPath]);
        }

        // Handle favicon upload
        if (!empty($_FILES['favicon_upload']['tmp_name']) && is_uploaded_file($_FILES['favicon_upload']['tmp_name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/media/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['favicon_upload']['name'], PATHINFO_EXTENSION);
            $faviconPath = '/uploads/media/favicon_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['favicon_upload']['tmp_name'], __DIR__ . '/../../../public' . $faviconPath);
            \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'favicon_path'], ['value' => $faviconPath]);
        }

        \Flight::redirect('/admin/settings?status=updated');
    }
<?php

namespace App\Controllers\Admin;

use Illuminate\Database\Capsule\Manager as Capsule;

class SettingsController
{
    public static function index()
    {
        // Load all relevant settings
        $keys = [
            'site_name', 'site_tagline', 'site_url', 'admin_email', 'logo_path', 'favicon_path',
            'default_language', 'timezone', 'date_format', 'time_format'
        ];
        $settings = \Illuminate\Database\Capsule\Manager::table('settings')
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();
        $app_url = $_ENV['APP_URL']
            ?? $_SERVER['APP_URL']
            ?? (getenv('APP_URL') ?: 'http://localhost');
        echo \Flight::get('blade')->render('admin.settings.index', compact('settings', 'app_url'));
    }

    public static function breadcrumbsIndex()
    {
        $defaults = self::getBreadcrumbDefaults();
        echo \Flight::get('blade')->render('admin.settings.breadcrumbs', compact('defaults'));
    }

    public static function breadcrumbsUpdate()
    {
        if (!Capsule::schema()->hasTable('settings')) {
            \Flight::redirect('/admin/settings/breadcrumbs?status=settings-missing');
            return;
        }

        $data = \Flight::request()->data->getData();

        $map = [
            'breadcrumbs_enabled' => $data['enabled'] ?? '0', // Checkbox value
            'breadcrumbs_type' => $data['type'] ?? 'auto',
            'breadcrumbs_show_home' => $data['show_home'] ?? '0', // Checkbox value
            'breadcrumbs_home_label' => $data['home_label'] ?? 'Home',
            'breadcrumbs_separator' => $data['separator'] ?? '/',
            'breadcrumbs_schema' => $data['schema'] ?? '0', // Checkbox value
        ];

        foreach ($map as $key => $value) {
            Capsule::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        \Flight::redirect('/admin/settings/breadcrumbs?status=updated');
    }

    /**
     * Handles the POST request to update general site settings.
     */
    public static function update()
    {
        // Ensure the settings table exists before attempting to update
        if (!Capsule::schema()->hasTable('settings')) {
            \Flight::redirect('/admin/settings?status=settings-missing');
            return;
        }

        $data = \Flight::request()->data->getData();
        $files = \Flight::request()->files->getData(); // Get uploaded files

        $settingsToUpdate = [];
        $redirectStatus = 'updated'; // Default success status

        // Define the upload directory for settings-related files
        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/settings/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle Logo Upload
        if (isset($files['logo_upload']) && $files['logo_upload']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $files['logo_upload'];
            $ext = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
            $allowedImageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

            if (in_array($ext, $allowedImageExts)) {
                $newLogoName = uniqid('logo_') . '.' . $ext;
                $targetPath = $uploadDir . $newLogoName;

                if (move_uploaded_file($logoFile['tmp_name'], $targetPath)) {
                    $settingsToUpdate['logo_path'] = '/uploads/settings/' . $newLogoName;
                    // Optional: Delete old logo file if a new one is successfully uploaded
                    $oldLogoPath = Capsule::table('settings')->where('key', 'logo_path')->value('value');
                    if ($oldLogoPath && file_exists(dirname(__DIR__, 3) . '/public' . $oldLogoPath)) {
                        unlink(dirname(__DIR__, 3) . '/public' . $oldLogoPath);
                    }
                } else {
                    $redirectStatus = 'logo-upload-failed';
                }
            } else {
                $redirectStatus = 'logo-invalid-type';
            }
        }

        // Handle Favicon Upload
        if (isset($files['favicon_upload']) && $files['favicon_upload']['error'] === UPLOAD_ERR_OK) {
            $faviconFile = $files['favicon_upload'];
            $ext = strtolower(pathinfo($faviconFile['name'], PATHINFO_EXTENSION));
            $allowedFaviconExts = ['ico', 'png', 'svg'];

            if (in_array($ext, $allowedFaviconExts)) {
                $newFaviconName = uniqid('favicon_') . '.' . $ext;
                $targetPath = $uploadDir . $newFaviconName;

                if (move_uploaded_file($faviconFile['tmp_name'], $targetPath)) {
                    $settingsToUpdate['favicon_path'] = '/uploads/settings/' . $newFaviconName;
                    $oldFaviconPath = Capsule::table('settings')->where('key', 'favicon_path')->value('value');
                    if ($oldFaviconPath && file_exists(dirname(__DIR__, 3) . '/public' . $oldFaviconPath)) {
                        unlink(dirname(__DIR__, 3) . '/public' . $oldFaviconPath);
                    }
                } else {
                    $redirectStatus = 'favicon-upload-failed';
                }
            } else {
                $redirectStatus = 'favicon-invalid-type';
            }
        }

        // Process other form fields. Note: 'site_url' is readonly in the form, so it's not included here.
        $formKeys = ['site_name', 'site_tagline', 'admin_email', 'default_language', 'timezone', 'date_format', 'time_format'];
        foreach ($formKeys as $key) {
            if (isset($data[$key])) {
                $settingsToUpdate[$key] = $data[$key];
            }
        }

        // Update or insert settings in a transaction for atomicity
        Capsule::transaction(function () use ($settingsToUpdate) {
            foreach ($settingsToUpdate as $key => $value) {
                Capsule::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        });

        \Flight::redirect('/admin/settings?status=' . $redirectStatus);
    }

    private static function getBreadcrumbDefaults(): array
    {
        $keys = [
            'breadcrumbs_enabled', 'breadcrumbs_type', 'breadcrumbs_show_home',
            'breadcrumbs_home_label', 'breadcrumbs_separator', 'breadcrumbs_schema',
        ];

        $settings = Capsule::table('settings')->whereIn('key', $keys)->pluck('value', 'key')->toArray();

        return [
            'enabled' => $settings['breadcrumbs_enabled'] ?? '0',
            'type' => $settings['breadcrumbs_type'] ?? 'auto',
            'show_home' => $settings['breadcrumbs_show_home'] ?? '0',
            'home_label' => $settings['breadcrumbs_home_label'] ?? 'Home',
            'separator' => $settings['breadcrumbs_separator'] ?? '/',
            'schema' => $settings['breadcrumbs_schema'] ?? '0',
        ];
    }
}