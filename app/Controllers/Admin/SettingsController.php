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
            'default_language', 'timezone', 'date_format', 'time_format', 'show_theme_credit'
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
            mkdir($uploadDir, 0755, true);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        // Handle Logo Upload
        if (isset($files['logo_upload']) && $files['logo_upload']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $files['logo_upload'];
            if (!isset($logoFile['tmp_name']) || !is_uploaded_file($logoFile['tmp_name'])) {
                $redirectStatus = 'logo-upload-failed';
            } else {
                $maxBytes = 2 * 1024 * 1024; // 2MB
                if (!empty($logoFile['size']) && (int)$logoFile['size'] > $maxBytes) {
                    $redirectStatus = 'logo-upload-failed';
                } else {
                    $mimeType = (string)($finfo->file($logoFile['tmp_name']) ?: '');
                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                    ];

                    if (!isset($allowed[$mimeType])) {
                        $redirectStatus = 'logo-invalid-type';
                    } else {
                        $ext = $allowed[$mimeType];
                        $newLogoName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetPath = $uploadDir . $newLogoName;

                        if (move_uploaded_file($logoFile['tmp_name'], $targetPath)) {
                            $settingsToUpdate['logo_path'] = '/uploads/settings/' . $newLogoName;

                            $oldLogoPath = (string) Capsule::table('settings')->where('key', 'logo_path')->value('value');
                            if (
                                $oldLogoPath &&
                                str_starts_with($oldLogoPath, '/uploads/settings/') &&
                                !str_contains($oldLogoPath, '..')
                            ) {
                                $oldFile = dirname(__DIR__, 3) . '/public' . $oldLogoPath;
                                if (is_file($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                        } else {
                            $redirectStatus = 'logo-upload-failed';
                        }
                    }
                }
            }
        }

        // Handle Favicon Upload
        if (isset($files['favicon_upload']) && $files['favicon_upload']['error'] === UPLOAD_ERR_OK) {
            $faviconFile = $files['favicon_upload'];
            if (!isset($faviconFile['tmp_name']) || !is_uploaded_file($faviconFile['tmp_name'])) {
                $redirectStatus = 'favicon-upload-failed';
            } else {
                $maxBytes = 512 * 1024; // 512KB
                if (!empty($faviconFile['size']) && (int)$faviconFile['size'] > $maxBytes) {
                    $redirectStatus = 'favicon-upload-failed';
                } else {
                    $mimeType = (string)($finfo->file($faviconFile['tmp_name']) ?: '');
                    $allowed = [
                        'image/png' => 'png',
                        'image/x-icon' => 'ico',
                        'image/vnd.microsoft.icon' => 'ico',
                    ];

                    if (!isset($allowed[$mimeType])) {
                        $redirectStatus = 'favicon-invalid-type';
                    } else {
                        $ext = $allowed[$mimeType];
                        $newFaviconName = 'favicon_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetPath = $uploadDir . $newFaviconName;

                        if (move_uploaded_file($faviconFile['tmp_name'], $targetPath)) {
                            $settingsToUpdate['favicon_path'] = '/uploads/settings/' . $newFaviconName;

                            $oldFaviconPath = (string) Capsule::table('settings')->where('key', 'favicon_path')->value('value');
                            if (
                                $oldFaviconPath &&
                                str_starts_with($oldFaviconPath, '/uploads/settings/') &&
                                !str_contains($oldFaviconPath, '..')
                            ) {
                                $oldFile = dirname(__DIR__, 3) . '/public' . $oldFaviconPath;
                                if (is_file($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                        } else {
                            $redirectStatus = 'favicon-upload-failed';
                        }
                    }
                }
            }
        }


        // Process other form fields. Note: 'site_url' is readonly in the form, so it's not included here.
        $formKeys = ['site_name', 'site_tagline', 'admin_email', 'default_language', 'timezone', 'date_format', 'time_format'];
        foreach ($formKeys as $key) {
            if (isset($data[$key])) {
                $settingsToUpdate[$key] = $data[$key];
            }
        }

        // Handle show_theme_credit checkbox (save as '1' if checked, '0' if not)
        $settingsToUpdate['show_theme_credit'] = !empty($data['show_theme_credit']) && $data['show_theme_credit'] == '1' ? '1' : '0';

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