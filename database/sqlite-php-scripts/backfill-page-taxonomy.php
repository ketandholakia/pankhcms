<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";

use Illuminate\Database\Capsule\Manager as Capsule;

$categoryName = "web";
$categorySlug = "web";
$tagName = "webpage";
$tagSlug = "webpage";

$categoryId = Capsule::table("categories")->where("slug", $categorySlug)->value("id");
if (!$categoryId) {
    $categoryId = Capsule::table("categories")->insertGetId([
        "name" => $categoryName,
        "slug" => $categorySlug,
        "parent_id" => null,
    ]);
    echo "✅ category created\n";
}

$tagId = Capsule::table("tags")->where("slug", $tagSlug)->value("id");
if (!$tagId) {
    $tagId = Capsule::table("tags")->insertGetId([
        "name" => $tagName,
        "slug" => $tagSlug,
    ]);
    echo "✅ tag created\n";
}

$pageIds = Capsule::table("pages")->pluck("id")->all();
foreach ($pageIds as $pageId) {
    $categoryExists = Capsule::table("category_page")
        ->where("category_id", $categoryId)
        ->where("page_id", $pageId)
        ->exists();
    if (!$categoryExists) {
        Capsule::table("category_page")->insert([
            "category_id" => $categoryId,
            "page_id" => $pageId,
        ]);
    }

    $tagExists = Capsule::table("page_tag")
        ->where("page_id", $pageId)
        ->where("tag_id", $tagId)
        ->exists();
    if (!$tagExists) {
        Capsule::table("page_tag")->insert([
            "page_id" => $pageId,
            "tag_id" => $tagId,
        ]);
    }
}

echo "\n🎉 Backfill complete\n";
