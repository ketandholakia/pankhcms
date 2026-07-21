<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('users')) {
    return;
}

if (!$schema->hasColumn('users', 'username')) {
    $schema->table('users', function ($table) {
        $table->string('username')->nullable()->after('id');
    });
}

$driver = Capsule::connection()->getDriverName();

$normalizeUsername = static function (?string $value): string {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9._-]+/', '_', $value) ?? '';
    $value = trim($value, '._-');

    if ($value === '') {
        $value = 'user';
    }

    if (strlen($value) > 50) {
        $value = rtrim(substr($value, 0, 50), '._-');
    }

    if ($value === '') {
        $value = 'user';
    }

    return $value;
};

$existingUsernames = [];
$users = Capsule::table('users')->orderBy('id')->get();

foreach ($users as $user) {
    $candidate = $user->username;

    if (!$candidate) {
        $candidate = $user->name;
    }

    if (!$candidate && !empty($user->email) && strpos((string) $user->email, '@') !== false) {
        $candidate = strstr((string) $user->email, '@', true);
    }

    $base = $normalizeUsername($candidate);
    $username = $base;
    $suffix = 2;

    while (
        isset($existingUsernames[$username]) ||
        Capsule::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()
    ) {
        $username = $base . $suffix;
        $suffix++;
    }

    Capsule::table('users')->where('id', $user->id)->update(['username' => $username]);
    $existingUsernames[$username] = true;
}

if ($driver === 'sqlite') {
    Capsule::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_username_unique ON users (username)');
} else {
    $indexes = Capsule::select("SHOW INDEX FROM users WHERE Key_name = 'users_username_unique'");
    if (empty($indexes)) {
        Capsule::statement('ALTER TABLE users ADD UNIQUE users_username_unique (username)');
    }
}
