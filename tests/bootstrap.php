<?php

$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/Core/PluginManager.php';

$_SERVER['REQUEST_URI'] = '/';

ob_start();
// Run migrations
require dirname(__DIR__) . '/scripts/migrate.php';

ob_end_clean();

// Seed the in-memory database with a user for auth testing
use App\Models\User;
use App\Models\Role;

$user = User::create([
    'username' => 'testadmin',
    'name' => 'Test Admin',
    'email' => 'admin@example.com',
    'password' => password_hash('password', PASSWORD_DEFAULT),
]);

$adminRole = Role::where('name', 'Administrator')->first();
if ($adminRole) {
    $user->roles()->attach($adminRole->id);
}
