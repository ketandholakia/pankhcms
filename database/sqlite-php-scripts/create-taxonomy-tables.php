<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable("categories")) {
    $schema->create("categories", function ($table) {
        $table->increments("id");
        $table->string("name");
        $table->string("slug")->unique();
        $table->integer("parent_id")->nullable();
    });
    echo "✅ categories created\n";
} else {
    echo "⚠️ categories already exist\n";
}

if (!$schema->hasTable("tags")) {
    $schema->create("tags", function ($table) {
        $table->increments("id");
        $table->string("name");
        $table->string("slug")->unique();
    });
    echo "✅ tags created\n";
} else {
    echo "⚠️ tags already exist\n";
}

if (!$schema->hasTable("category_page")) {
    $schema->create("category_page", function ($table) {
        $table->integer("category_id");
        $table->integer("page_id");
    });
    echo "✅ category_page created\n";
} else {
    echo "⚠️ category_page already exists\n";
}

if (!$schema->hasTable("page_tag")) {
    $schema->create("page_tag", function ($table) {
        $table->integer("page_id");
        $table->integer("tag_id");
    });
    echo "✅ page_tag created\n";
} else {
    echo "⚠️ page_tag already exists\n";
}

echo "\n🎉 Taxonomy tables ready\n";
