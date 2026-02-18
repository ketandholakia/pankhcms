<?php

namespace App\Controllers\Admin;

use Illuminate\Database\Capsule\Manager as Capsule;

class SettingsController
{
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