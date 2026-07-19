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

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $newApiToken = $_SESSION['new_api_token'] ?? null;
        unset($_SESSION['new_api_token']);

        $apiTokens = $user->apiTokens()->orderBy('created_at', 'desc')->get();

        echo \Flight::get('blade')->render('admin.profile', [
            'user' => $user,
            'apiTokens' => $apiTokens,
            'newApiToken' => $newApiToken,
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

    public static function generateApiToken()
    {
        $user = Auth::user();
        if (!$user) {
            \Flight::redirect('/admin/login');
            return;
        }

        $data = \Flight::request()->data->getData();
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            \Flight::redirect('/admin/profile?status=token-name-missing');
            return;
        }

        $plainTextToken = bin2hex(random_bytes(30));
        $hashedToken = hash('sha256', $plainTextToken);

        $user->apiTokens()->create([
            'name' => $name,
            'token' => $hashedToken,
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['new_api_token'] = $plainTextToken;

        \Flight::redirect('/admin/profile?status=token-created');
    }

    public static function revokeApiToken($id)
    {
        $user = Auth::user();
        if (!$user) {
            \Flight::redirect('/admin/login');
            return;
        }

        $token = $user->apiTokens()->where('id', $id)->first();
        if ($token) {
            $token->delete();
        }

        \Flight::redirect('/admin/profile?status=token-revoked');
    }

    public static function index()
    {
        $users = User::with('roles')->get();
        echo \Flight::get('blade')->render('admin.users.index', compact('users'));
    }

    public static function create()
    {
        $roles = \App\Models\Role::all();
        echo \Flight::get('blade')->render('admin.users.form', ['user' => new User(), 'roles' => $roles, 'action' => '/admin/users/store']);
    }

    public static function store()
    {
        $data = \Flight::request()->data->getData();
        
        if (empty($data['password'])) {
            \Flight::redirect('/admin/users/create?error=password_required');
            return;
        }

        $user = new User();
        $user->name = $data['name'] ?? null;
        $user->email = $data['email'] ?? '';
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();

        if (!empty($data['role_id'])) {
            $user->roles()->attach($data['role_id']);
        }

        \Flight::redirect('/admin/users?status=created');
    }

    public static function edit($id)
    {
        $user = User::with('roles')->find($id);
        if (!$user) \Flight::redirect('/admin/users');
        
        $roles = \App\Models\Role::all();
        echo \Flight::get('blade')->render('admin.users.form', ['user' => $user, 'roles' => $roles, 'action' => "/admin/users/update/{$id}"]);
    }

    public static function update($id)
    {
        $user = User::find($id);
        if (!$user) \Flight::redirect('/admin/users');

        $data = \Flight::request()->data->getData();
        $user->name = $data['name'] ?? null;
        $user->email = $data['email'] ?? $user->email;
        if (!empty($data['password'])) {
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $user->save();

        if (isset($data['role_id'])) {
            $user->roles()->sync([$data['role_id']]);
        }

        \Flight::redirect('/admin/users?status=updated');
    }

    public static function destroy($id)
    {
        if ($id != Auth::user()->id) {
            User::destroy($id);
        }
        \Flight::redirect('/admin/users?status=deleted');
    }
}
