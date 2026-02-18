<?php

namespace App\Controllers\Admin;

use Illuminate\Database\Capsule\Manager as Capsule;

class SeoController
{
    public static function index()
    {
        $defaults = self::getDefaults();
        echo \Flight::get('blade')->render('admin.settings.seo', compact('defaults'));
    }

    public static function update()
    {
        if (!Capsule::schema()->hasTable('settings')) {
            \Flight::redirect('/admin/settings/seo?status=settings-missing');
            return;
        }

        $data = \Flight::request()->data->getData();

        $title = trim((string) ($data['default_title'] ?? ''));
        $description = trim((string) ($data['default_description'] ?? ''));
        $keywords = trim((string) ($data['default_keywords'] ?? ''));
        $ogTitle = trim((string) ($data['og_title_default'] ?? ''));
        $ogDescription = trim((string) ($data['og_description_default'] ?? ''));
        $ogImage = trim((string) ($data['og_image_default'] ?? ''));
        $canonicalBase = trim((string) ($data['canonical_base'] ?? ''));
        $robotsDefault = trim((string) ($data['robots_default'] ?? ''));
        $twitterCard = trim((string) ($data['twitter_card'] ?? ''));
        $twitterSite = trim((string) ($data['twitter_site'] ?? ''));

        Capsule::table('settings')->updateOrInsert(
            ['key' => 'seo_default_title'],
            ['value' => $title]
        );

        Capsule::table('settings')->updateOrInsert(
            ['key' => 'seo_default_description'],
            ['value' => $description]
        );

        Capsule::table('settings')->updateOrInsert(
            ['key' => 'seo_default_keywords'],
            ['value' => $keywords]
        );

        Capsule::table('settings')->updateOrInsert(
            ['key' => 'og_title_default'],
            ['value' => $ogTitle]
        );
        Capsule::table('settings')->updateOrInsert(
            ['key' => 'og_description_default'],
            ['value' => $ogDescription]
        );
        Capsule::table('settings')->updateOrInsert(
            ['key' => 'og_image_default'],
            ['value' => $ogImage]
        );
        Capsule::table('settings')->updateOrInsert(
            ['key' => 'canonical_base'],
            ['value' => $canonicalBase]
        );
        Capsule::table('settings')->updateOrInsert(
            ['key' => 'robots_default'],
            ['value' => $robotsDefault]
        );
        Capsule::table('settings')->updateOrInsert(
            ['key' => 'twitter_card'],
            ['value' => $twitterCard]
        );
        Capsule::table('settings')->updateOrInsert(
            ['key' => 'twitter_site'],
            ['value' => $twitterSite]
        );

        \Flight::redirect('/admin/settings/seo?status=updated');
    }
    private static function getDefaults(): array
    {
        $defaults = [
            'default_title' => '',
            'default_description' => '',
            'default_keywords' => '',
            'og_title_default' => '',
            'og_description_default' => '',
            'og_image_default' => '',
            'canonical_base' => '',
            'robots_default' => '',
            'twitter_card' => '',
            'twitter_site' => '',
        ];

        try {
            if (!Capsule::schema()->hasTable('settings')) {
                return $defaults;
            }

            $settings = Capsule::table('settings')
                ->whereIn('key', [
                    'seo_default_title',
                    'seo_default_description',
                    'seo_default_keywords',
                    'og_title_default',
                    'og_description_default',
                    'og_image_default',
                    'canonical_base',
                    'robots_default',
                    'twitter_card',
                    'twitter_site',
                ])
                ->pluck('value', 'key')
                ->toArray();

            $defaults['default_title'] = (string) ($settings['seo_default_title'] ?? '');
            $defaults['default_description'] = (string) ($settings['seo_default_description'] ?? '');
            $defaults['default_keywords'] = (string) ($settings['seo_default_keywords'] ?? '');
            $defaults['og_title_default'] = (string) ($settings['og_title_default'] ?? '');
            $defaults['og_description_default'] = (string) ($settings['og_description_default'] ?? '');
            $defaults['og_image_default'] = (string) ($settings['og_image_default'] ?? '');
            $defaults['canonical_base'] = (string) ($settings['canonical_base'] ?? '');
            $defaults['robots_default'] = (string) ($settings['robots_default'] ?? '');
            $defaults['twitter_card'] = (string) ($settings['twitter_card'] ?? '');
            $defaults['twitter_site'] = (string) ($settings['twitter_site'] ?? '');
        } catch (\Throwable $e) {
            return $defaults;
        }

        return $defaults;
    }
}
