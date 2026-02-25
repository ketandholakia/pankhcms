<?php
class Hooks
{
    private static $actions = [];
    public static function add($hook, $callback)
    {
        self::$actions[$hook][] = $callback;
    }
    public static function run($hook, ...$args)
    {
        if (!isset(self::$actions[$hook])) return;
        foreach (self::$actions[$hook] as $cb) {
            call_user_func_array($cb, $args);
        }
    }
}
