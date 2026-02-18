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
        $email = $_POST["email"];
        $password = $_POST["password"];

        if (Auth::attempt($email, $password)) {
            \Flight::redirect("/admin");
        }

        echo "Invalid login";
    }

    public static function logout()
    {
        Auth::logout();
        \Flight::redirect("/admin/login");
        exit;
    }
}
