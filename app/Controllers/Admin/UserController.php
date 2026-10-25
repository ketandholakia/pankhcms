<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Role;
use App\Models\User;

class UserController
{
    protected static function normalizeUsername(?string $username): string
    {
        $username = strtolower(trim((string) $username));
        $username = preg_replace('/[^a-z0-9._-]+/', '_', $username) ?? '';
        $username = trim($username, '._-');

        return $username;
    }

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

        $username = self::normalizeUsername($data['username'] ?? '');
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $email = isset($data['email']) ? trim((string) $data['email']) : '';

        if ($username === '') {
            \Flight::redirect('/admin/profile?status=username-required');
            return;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \Flight::redirect('/admin/profile?status=invalid-email');
            return;
        }

        $usernameTaken = User::where('username', $username)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($usernameTaken) {
            \Flight::redirect('/admin/profile?status=username-taken');
            return;
        }

        $emailTaken = User::where('email', $email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            \Flight::redirect('/admin/profile?status=email-taken');
            return;
        }

        $user->username = $username;
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

        // Same policy as the reset flow: 10+ chars with upper, lower,
        // number and symbol.
        $policyErrors = password_policy_errors($new);
        if (!empty($policyErrors)) {
            \Flight::redirect('/admin/profile?status=password-policy&errors=' . urlencode(implode(' ', $policyErrors)));
            return;
        }

        if ($new !== $confirm) {
            \Flight::redirect('/admin/profile?status=password-mismatch');
            return;
        }

        $user->password = password_hash($new, PASSWORD_DEFAULT);
        $user->save();

        // A password change invalidates every API token; the current web
        // session is re-bound below and stays logged in.
        $user->apiTokens()->delete();

        // Re-bind the current session to the new hash so the user stays
        // logged in here; every OTHER session for this account becomes
        // invalid on its next request (see Auth::check()).
        $_SESSION['password_hash'] = $user->password;
        session_regenerate_id(true);

        // The memoized user still carries the old hash; drop it.
        Auth::forgetUser();

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

        // Optional expiry in days (blank = never expires)
        $expiresInDays = (int) ($data['expires_in_days'] ?? 0);
        $expiresAt = $expiresInDays > 0
            ? \Illuminate\Support\Carbon::now()->addDays(min($expiresInDays, 3650))
            : null;

        $user->apiTokens()->create([
            'name' => $name,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
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
        $username = self::normalizeUsername($data['username'] ?? '');
        $email = trim((string) ($data['email'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        
        if ($username === '') {
            \Flight::redirect('/admin/users/create?error=username_required');
            return;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \Flight::redirect('/admin/users/create?error=invalid_email');
            return;
        }

        if (empty($data['password'])) {
            \Flight::redirect('/admin/users/create?error=password_required');
            return;
        }

        $passwordErrors = password_policy_errors((string) $data['password']);
        if (!empty($passwordErrors)) {
            \Flight::redirect('/admin/users/create?error=password_policy&errors=' . urlencode(implode(' ', $passwordErrors)));
            return;
        }

        if (User::where('username', $username)->exists()) {
            \Flight::redirect('/admin/users/create?error=username_taken');
            return;
        }

        if (User::where('email', $email)->exists()) {
            \Flight::redirect('/admin/users/create?error=email_taken');
            return;
        }

        // The role selector must reference a real role.
        if (!empty($data['role_id']) && !Role::find((int) $data['role_id'])) {
            \Flight::redirect('/admin/users/create?error=invalid_role');
            return;
        }

        $user = new User();
        $user->username = $username;
        $user->name = $name !== '' ? $name : null;
        $user->email = $email;
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();

        if (!empty($data['role_id'])) {
            $user->roles()->attach((int) $data['role_id']);
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
        $username = self::normalizeUsername($data['username'] ?? '');
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? $user->email));

        if ($username === '') {
            \Flight::redirect("/admin/users/edit/{$id}?error=username_required");
            return;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            \Flight::redirect("/admin/users/edit/{$id}?error=invalid_email");
            return;
        }

        if (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
            \Flight::redirect("/admin/users/edit/{$id}?error=username_taken");
            return;
        }

        if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            \Flight::redirect("/admin/users/edit/{$id}?error=email_taken");
            return;
        }

        // The role selector must reference a real role.
        if (isset($data['role_id']) && $data['role_id'] !== '' && !Role::find((int) $data['role_id'])) {
            \Flight::redirect("/admin/users/edit/{$id}?error=invalid_role");
            return;
        }

        // Never let a role change remove the last user holding manage_users
        // (e.g. an admin demoting themselves out of the Administrator role).
        if (isset($data['role_id']) && $user->hasPermission('manage_users')) {
            $newRole = $data['role_id'] !== '' ? Role::find((int) $data['role_id']) : null;
            $newRoleGrantsManageUsers = $newRole !== null
                && $newRole->permissions->contains('name', 'manage_users');

            if (!$newRoleGrantsManageUsers && self::countManageUsersUsers() <= 1) {
                \Flight::redirect('/admin/users?status=last_admin');
                return;
            }
        }

        $user->username = $username;
        $user->name = $name !== '' ? $name : null;
        $user->email = $email;
        if (!empty($data['password'])) {
            // Blank password on update means "keep the current one"; a
            // supplied password must satisfy the same policy as the
            // profile self-service change and the reset flow.
            $passwordErrors = password_policy_errors((string) $data['password']);
            if (!empty($passwordErrors)) {
                \Flight::redirect("/admin/users/edit/{$id}?error=password_policy&errors=" . urlencode(implode(' ', $passwordErrors)));
                return;
            }
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            // An admin-set password also invalidates the user's API tokens.
            $user->apiTokens()->delete();
        }
        $user->save();

        if (isset($data['role_id'])) {
            // "-- No Role --" submits an empty string; sync([]) detaches
            // every role. sync(['']) would insert a bogus role_id='' pivot
            // row (a strict-mode SQL error on MySQL/MariaDB, and a corrupt
            // row elsewhere).
            $user->roles()->sync($data['role_id'] === '' ? [] : [$data['role_id']]);
        }

        \Flight::redirect('/admin/users?status=updated');
    }

    public static function destroy($id)
    {
        $actor = Auth::user();

        if ($actor && $id != $actor->id) {
            $target = User::with('roles')->find($id);

            if ($target) {
                // Never delete the last user whose roles can manage users.
                if ($target->hasPermission('manage_users') && self::countManageUsersUsers() <= 1) {
                    \Flight::redirect('/admin/users?status=last_admin');
                    return;
                }

                $target->roles()->detach();
                $target->delete();
            }
        }

        \Flight::redirect('/admin/users?status=deleted');
    }

    /**
     * Number of users whose roles grant manage_users. Shared by the
     * delete and role-change guards so "last admin" means the same thing
     * everywhere.
     */
    private static function countManageUsersUsers(): int
    {
        return User::whereHas('roles.permissions', function ($q) {
            $q->where('name', 'manage_users');
        })->count();
    }
}
