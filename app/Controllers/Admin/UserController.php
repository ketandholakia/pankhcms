<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\User;

class UserController
{
    public static function editProfile()
    {
        $user = Auth::user();

        if (!$user) {
            \Flight::redirect('/admin/login');
            return;
        }

        echo \Flight::get('blade')->render('admin.profile', [
            'user' => $user,
        ]);
    }

    public static function updateProfile()
    {
        $user = Auth::user();

        if (!$user) {
            \Flight::redirect('/admin/login');
            return;
        }

        $data = \Flight::request()->data->getData();

        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $email = isset($data['email']) ? trim((string) $data['email']) : '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \Flight::redirect('/admin/profile?status=invalid-email');
            return;
        }

        $emailTaken = User::where('email', $email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            \Flight::redirect('/admin/profile?status=email-taken');
            return;
        }

        $user->name = $name !== '' ? $name : null;
        $user->email = $email;
        $user->save();

        \Flight::redirect('/admin/profile?status=profile-updated');
    }

    public static function updatePassword()
    {
        $user = Auth::user();

        if (!$user) {
            \Flight::redirect('/admin/login');
            return;
        }

        $data = \Flight::request()->data->getData();

        $current = isset($data['current_password']) ? (string) $data['current_password'] : '';
        $new = isset($data['new_password']) ? (string) $data['new_password'] : '';
        $confirm = isset($data['new_password_confirmation']) ? (string) $data['new_password_confirmation'] : '';

        if ($current === '' || $new === '' || $confirm === '') {
            \Flight::redirect('/admin/profile?status=password-missing');
            return;
        }

        if (!password_verify($current, (string) $user->password)) {
            \Flight::redirect('/admin/profile?status=password-current-invalid');
            return;
        }

        if (strlen($new) < 8) {
            \Flight::redirect('/admin/profile?status=password-too-short');
            return;
        }

        if ($new !== $confirm) {
            \Flight::redirect('/admin/profile?status=password-mismatch');
            return;
        }

        $user->password = password_hash($new, PASSWORD_DEFAULT);
        $user->save();

        session_regenerate_id(true);

        \Flight::redirect('/admin/profile?status=password-updated');
    }
}
