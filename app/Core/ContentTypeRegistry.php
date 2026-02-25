<?php

class ContentTypeRegistry
{
    private static $types = [];

    public static function register(array $type)
    {
        $slug = $type['slug'] ?? null;
        if (!$slug) return;
        self::$types[$slug] = $type;
    }

    public static function all()
    {
        return self::$types;
    }
}
