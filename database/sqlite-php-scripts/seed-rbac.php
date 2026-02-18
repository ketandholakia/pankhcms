<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";


use Illuminate\Database\Capsule\Manager as DB;

// Roles
$roles = ['admin', 'editor', 'author', 'viewer'];

foreach ($roles as $r) {
    DB::table('roles')->insertOrIgnore(['name' => $r]);
}

// Permissions
$permissions = [
    'manage_users',
    'manage_pages',
    'edit_pages',
    'create_pages',
    'delete_pages',
    'view_admin'
];

foreach ($permissions as $p) {
    DB::table('permissions')->insertOrIgnore(['name' => $p]);
}

echo "✅ Roles & permissions seeded\n";
