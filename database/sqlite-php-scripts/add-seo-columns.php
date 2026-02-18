<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";

use Illuminate\Database\Capsule\Manager as Capsule;

$table = "pages";

$columns = [
    "meta_title TEXT",
    "meta_description TEXT",
    "meta_keywords TEXT",
    "og_image TEXT",
    "canonical_url TEXT",
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

echo "\n🎉 SEO columns update complete!\n";
