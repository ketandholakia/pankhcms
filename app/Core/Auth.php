<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function user()
    {
        return self::check()
            ? User::find($_SESSION['user_id'])
            : null;
    }

    public static function attempt($email, $password)
    {
        $user = User::where('email', $email)->first();

        if ($user && password_verify($password, $user->password)) {
            session_init();

            if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
                $user->password = password_hash($password, PASSWORD_DEFAULT);
                $user->save();
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            return true;
        }

        return false;
    }

    public static function logout()
    {
        session_init();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        session_init();
        session_regenerate_id(true);
    }
}
