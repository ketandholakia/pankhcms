<?php

namespace App\Controllers\Admin;

use App\Models\Page;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Template;

class PageController
{
    public static function index()
    {
        $pages = Page::with(['categories', 'tags'])->get();

        echo \Flight::get("blade")->render(
            "admin.pages.index",
            compact("pages"),
        );
    }

    public static function create()
    {
        $templates = Template::all();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        echo \Flight::get("blade")->render("admin.pages.create", compact('templates', 'categories', 'tags'));
    }

    public static function store()
    {
        $input = $_POST;

        $slug = $_POST["slug"]
            ? unique_slug($_POST["slug"])
            : unique_slug($_POST["title"]);

        $page = Page::create([
            "title" => $_POST["title"],
            "slug" => $slug,
            'content_json' => $input['content_json'] ?? null,
            'meta_title' => ($input['meta_title'] ?? '') ?: null,
            'meta_description' => ($input['meta_description'] ?? '') ?: null,
            'meta_keywords' => ($input['meta_keywords'] ?? '') ?: null,
            'og_title' => ($input['og_title'] ?? '') ?: null,
            'og_description' => ($input['og_description'] ?? '') ?: null,
            'og_image' => ($input['og_image'] ?? '') ?: null,
            'canonical_url' => ($input['canonical_url'] ?? '') ?: null,
            'robots' => ($input['robots'] ?? '') ?: null,
            'twitter_card' => ($input['twitter_card'] ?? '') ?: null,
        ]);

        $categoryIds = isset($_POST['category_ids']) ? (array) $_POST['category_ids'] : [];
        $tagIds = isset($_POST['tag_ids']) ? (array) $_POST['tag_ids'] : [];
        $page->categories()->sync($categoryIds);
        $page->tags()->sync($tagIds);

        \Flight::redirect("/admin/pages?saved=1");
    }

    public static function edit($id)
    {
        $page = Page::findOrFail($id);
        $templates = Template::all();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        echo \Flight::get("blade")->render("admin.pages.edit", compact("page", "templates", "categories", "tags"));
    }

    public static function update($id)
    {
        $page = Page::findOrFail($id);
        $input = $_POST;

        $slug = $_POST["slug"]
            ? unique_slug($_POST["slug"], $id)
            : unique_slug($_POST["title"], $id);

        $page->update([
            "title" => $_POST["title"],
            "slug" => $slug,
            'content_json' => $input['content_json'] ?? null,
            'meta_title' => ($input['meta_title'] ?? '') ?: null,
            'meta_description' => ($input['meta_description'] ?? '') ?: null,
            'meta_keywords' => ($input['meta_keywords'] ?? '') ?: null,
            'og_title' => ($input['og_title'] ?? '') ?: null,
            'og_description' => ($input['og_description'] ?? '') ?: null,
            'og_image' => ($input['og_image'] ?? '') ?: null,
            'canonical_url' => ($input['canonical_url'] ?? '') ?: null,
            'robots' => ($input['robots'] ?? '') ?: null,
            'twitter_card' => ($input['twitter_card'] ?? '') ?: null,
        ]);

        $categoryIds = isset($_POST['category_ids']) ? (array) $_POST['category_ids'] : [];
        $tagIds = isset($_POST['tag_ids']) ? (array) $_POST['tag_ids'] : [];
        $page->categories()->sync($categoryIds);
        $page->tags()->sync($tagIds);

        \Flight::redirect("/admin/pages?saved=1");
    }
}
