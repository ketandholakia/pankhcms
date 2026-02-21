<?php

namespace App\Controllers;

use App\Core\Auth;

class AuthController
{
    public static function showLogin()
    {
        echo \Flight::get("blade")->render("admin.login");
    }

    public static function login()
    {
        $email = isset($_POST["email"]) ? trim((string) $_POST["email"]) : '';
        $password = isset($_POST["password"]) ? (string) $_POST["password"] : '';

        $ip = client_ip();
        $key = login_throttle_key($email ?: 'unknown', $ip);
        $check = login_throttle_check($key);
        if (!$check['allowed']) {
            \Flight::response()->status(429);
            $retry = (int) $check['retry_after'];
            echo 'Too many login attempts. Try again in ' . $retry . ' seconds.';
            return;
        }

        if (Auth::attempt($email, $password)) {
            login_throttle_clear($key);
            \Flight::redirect("/admin");
            return;
        }

        $failure = login_throttle_register_failure($key);
        if (!empty($failure['locked']) && !empty($failure['retry_after'])) {
            \Flight::response()->status(429);
            echo 'Too many login attempts. Try again in ' . (int) $failure['retry_after'] . ' seconds.';
            return;
        }

        \Flight::response()->status(401);
        echo "Invalid login";
    }

    public static function logout()
    {
        Auth::logout();
        \Flight::redirect("/admin/login");
        exit;
    }
}
