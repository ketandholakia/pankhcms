<?php

namespace App\Core;

class Translator
{
    protected static $lang = 'en';
    protected static $messages = [];

    public static function setLocale($lang)
    {
        self::$lang = $lang;
        self::loadMessages();
    }

    protected static function loadMessages()
    {
        $file = dirname(__DIR__, 2) . '/resources/lang/' . self::$lang . '/messages.php';
        if (file_exists($file)) {
            self::$messages = require $file;
        } else {
            self::$messages = [];
        }
    }

    public static function get($key, $default = null)
    {
        if (empty(self::$messages)) {
            self::loadMessages();
        }
        
        return self::$messages[$key] ?? $default ?? $key;
    }
}
