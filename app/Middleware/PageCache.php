<?php

namespace App\Middleware;

class PageCache
{
    protected static $cacheDir;
    protected static $cacheFile;
    protected static $cacheEnabled = true;

    public static function before()
    {
        // Don't cache admin routes, POST requests, or logged-in users
        if (!self::shouldCache()) {
            return true;
        }

        self::$cacheDir = dirname(__DIR__, 2) . '/storage/cache/pages';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $slug = md5($uri);
        self::$cacheFile = self::$cacheDir . '/' . $slug . '.html';

        // Check if cache exists and is fresh (e.g., 60 minutes)
        if (file_exists(self::$cacheFile) && (time() - filemtime(self::$cacheFile) < 3600)) {
            header('X-Pankh-Cache: HIT');
            readfile(self::$cacheFile);
            exit;
        }

        // Start output buffering to capture the response
        ob_start();
        return true;
    }

    public static function after()
    {
        if (self::$cacheFile && self::shouldCache()) {
            $content = ob_get_flush();
            file_put_contents(self::$cacheFile, $content);
        }
    }
    
    public static function clear()
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/pages';
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    protected static function shouldCache()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') return false;
        
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (str_starts_with($uri, '/admin')) return false;
        if (str_starts_with($uri, '/install')) return false;
        
        // Disable cache if logged in
        if (\App\Core\Auth::check()) return false;
        
        return self::$cacheEnabled;
    }
}
