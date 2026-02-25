<?php

class AdminMenu
{
    private static $items = [];

    public static function add(array $item)
    {
        self::$items[] = $item;
    }

    public static function items(): array
    {
        return self::$items;
    }
}
