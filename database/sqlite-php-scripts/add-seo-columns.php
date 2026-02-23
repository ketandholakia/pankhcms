<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";

use Illuminate\Database\Capsule\Manager as Capsule;

$table = "pages";

$columns = [
    "seo_title TEXT",
    "seo_description TEXT",
    "seo_keywords TEXT",
    "seo_image TEXT",
    "meta_title TEXT",
    "meta_description TEXT",
    "meta_keywords TEXT",
    "og_image TEXT",
    "canonical_url TEXT",
    "robots TEXT",
    "noindex INTEGER DEFAULT 0",
];

foreach ($columns as $colDef) {
    $colName = explode(" ", $colDef)[0];

    // Check if column already exists
    $exists = Capsule::select("
        PRAGMA table_info($table)
    ");

    $found = false;

    foreach ($exists as $c) {
        if ($c->name === $colName) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        Capsule::statement("
            ALTER TABLE $table ADD COLUMN $colDef
        ");

        echo "✅ Added column: $colName\n";
    } else {
        echo "⚠️ Column already exists: $colName\n";
    }
}

$defaultSettings = [
    'site_title' => 'PankhCMS',
    'tagline' => 'Lightweight PHP CMS',
    'site_url' => getenv('APP_URL') ?: '',
    'default_meta_description' => 'Modern CMS for fast websites',
    'default_meta_keywords' => 'cms, php, website',
    'favicon' => '/uploads/favicon.ico',
    'og_image' => '/uploads/og.jpg',
];

if (Capsule::schema()->hasTable('settings')) {
    foreach ($defaultSettings as $key => $value) {
        Capsule::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        echo "✅ Upserted setting: {$key}\n";
    }
}

echo "\n🎉 SEO columns update complete!\n";
