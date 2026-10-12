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
            // Mirror Flight's own convention: don't exit under PHPUnit so
            // tests can assert on the redirect response.
            if (empty(getenv('PHPUNIT_TEST'))) {
                exit;
            }
            return false;
        }
        return true;
    }
}
