<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    protected static $statelessUser = null;

    /** @var User|null|false the resolved user for this request (false = not yet resolved) */
    protected static $memoUser = false;

    public static function setStatelessUser($user)
    {
        self::$statelessUser = $user;
    }

    /** Drop the per-request user memo (logout, password change). */
    public static function forgetUser(): void
    {
        self::$memoUser = false;
    }

    public static function check()
    {
        if (self::$statelessUser !== null) {
            return true;
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // A memoized user for a DIFFERENT session user must never be
        // reused (tests and multi-user flows switch sessions in one
        // process).
        if (self::$memoUser !== false && (self::$memoUser === null || (int) self::$memoUser->id !== (int) $_SESSION['user_id'])) {
            self::forgetUser();
        }

        // Session validation stays live (a deleted user or a changed
        // password must invalidate immediately), but as ONE light
        // single-column query instead of a full find per call.
        $currentHash = User::where('id', $_SESSION['user_id'])->value('password');

        // A session whose user row no longer exists is not authenticated.
        if (!is_string((string) $currentHash) || (string) $currentHash === '') {
            self::$memoUser = null;
            return false;
        }

        // Bind the session to the password hash: once the password is
        // changed or reset, every other session for that account dies.
        if (!isset($_SESSION['password_hash'])) {
            $_SESSION['password_hash'] = $currentHash;
        } elseif (!is_string($_SESSION['password_hash']) || !hash_equals($currentHash, $_SESSION['password_hash'])) {
            self::logout();
            return false;
        }

        // Resolve the full user (roles.permissions eager-loaded once:
        // hasPermission() then runs without further queries) only when
        // the memo is not already populated for this session user.
        if (self::$memoUser === false) {
            $user = User::with('roles.permissions')->find($_SESSION['user_id']);
            self::$memoUser = $user ?: null;
        }

        return true;
    }

    public static function user()
    {
        if (self::$statelessUser !== null) {
            return self::$statelessUser;
        }

        if (self::$memoUser === false) {
            self::check();
        }

        return self::$memoUser !== false ? self::$memoUser : null;
    }

    public static function attempt($identifier, $password)
    {
        // A fresh login is a new identity for this request context.
        self::forgetUser();

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
        self::forgetUser();
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
