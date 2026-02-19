<?php

/**
 * Build a recursive tree structure from flat menu items
 * (alias for build_menu_tree - for consistency with existing code)
 *
 * @param \Illuminate\Support\Collection $items
 * @param int|null $parentId
 * @return array
 */
function buildMenuTree($items, $parentId = null)
{
    return build_menu_tree($items, $parentId);
}

if (!function_exists('base_path')) {
    /**
     * Return the base path of the project.
     * Illuminate's SQLiteConnector may call this helper when resolving relative paths.
     */
    function base_path($path = '')
    {
        $base = dirname(__DIR__, 2);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}
