<?php
if (!function_exists('build_menu_tree')) {
    function build_menu_tree($items, $parent = null)
    {
        $branch = [];
        foreach ($items as $item) {
            if ($item->parent_id == $parent) {
                $children = build_menu_tree($items, $item->id);
                if ($children) {
                    $item->children = $children;
                }
                $branch[] = $item;
            }
        }
        return $branch;
    }
}

use App\Models\Page;

if (!function_exists("env")) {
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case "true":
            case "(true)":
                return true;
            case "false":
            case "(false)":
                return false;
            case "empty":
            case "(empty)":
                return "";
            case "null":
            case "(null)":
                return;
        }

        if (
            defined("STDIN") &&
            strlen($value) > 1 &&
            $value[0] === '"' &&
            $value[strlen($value) - 1] === '"'
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists("generate_slug")) {
    function generate_slug($text)
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Replace non letters/digits with hyphen
        $text = preg_replace("/[^a-z0-9]+/", "-", $text);

        // Remove duplicate hyphens
        $text = trim($text, "-");

        return $text ?: "page";
    }
}

if (!function_exists("unique_slug")) {
    function unique_slug($title, $id = null)
    {
        $slug = generate_slug($title);
        $original = $slug;
        $i = 1;

        while (true) {
            $query = Page::where("slug", $slug);

            // Ignore current page when editing
            if ($id) {
                $query->where("id", "!=", $id);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $original . "-" . $i++;
        }
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $settings = [];

        if (empty($settings)) {
            try {
                if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('settings')) {
                    $allSettings = \Illuminate\Database\Capsule\Manager::table('settings')->pluck('value', 'key')->toArray();
                    $settings = $allSettings;
                }
            } catch (\Throwable $e) { /* Settings table not ready */ }
        }

        return $settings[$key] ?? $default;
    }
}
