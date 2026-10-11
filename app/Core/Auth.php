<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    protected static $statelessUser = null;

    public static function setStatelessUser($user)
    {
        self::$statelessUser = $user;
    }

    public static function check()
    {
        if (self::$statelessUser !== null) {
            return true;
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // A session whose user row no longer exists is not authenticated.
        $user = User::find($_SESSION['user_id']);
        if (!$user) {
            return false;
        }

        // Bind the session to the password hash: once the password is
        // changed or reset, every other session for that account dies.
        if (!isset($_SESSION['password_hash'])) {
            $_SESSION['password_hash'] = $user->password;
        } elseif (!is_string($_SESSION['password_hash']) || !hash_equals($user->password, $_SESSION['password_hash'])) {
            self::logout();
            return false;
        }

        return true;
    }

    public static function user()
    {
        if (self::$statelessUser !== null) {
            return self::$statelessUser;
        }

        return self::check()
            ? User::find($_SESSION['user_id'])
            : null;
    }

    public static function attempt($identifier, $password)
    {
        $identifier = trim((string) $identifier);
        $usernameIdentifier = strtolower($identifier);

        $user = User::where('email', $identifier)
            ->orWhere('username', $usernameIdentifier)
            ->first();

        if ($user && password_verify($password, $user->password)) {
            session_init();

            if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
                $user->password = password_hash($password, PASSWORD_DEFAULT);
                $user->save();
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['password_hash'] = $user->password;
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
