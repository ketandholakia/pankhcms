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
