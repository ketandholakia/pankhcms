<?php

class Event
{
    private static $listeners = [];

    public static function listen(string $event, callable $cb)
    {
        self::$listeners[$event][] = $cb;
    }

    public static function dispatch(string $event, ...$args)
    {
        if (empty(self::$listeners[$event])) return;
        foreach (self::$listeners[$event] as $cb) {
            call_user_func_array($cb, $args);
        }
    }
}
