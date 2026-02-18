<?php

namespace App\Middleware;

use App\Core\Auth;

class AdminMiddleware
{
    public function before()
    {
        return self::handle();
    }

    public static function handle()
    {
        if (!Auth::check()) {
            \Flight::redirect("/admin/login");
            exit;
        }
        return true;
    }
}
