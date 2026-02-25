<?php

class BlockRegistry
{
    private static $blocks = [];

    public static function register(array $block)
    {
        $slug = $block['type'] ?? $block['slug'] ?? null;
        if (!$slug) return;
        self::$blocks[$slug] = $block;
    }

    public static function all()
    {
        return self::$blocks;
    }
}
