<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";


use Illuminate\Database\Capsule\Manager as DB;

$userId = 1; // admin user ID

$roleId = DB::table('roles')
    ->where('name', 'admin')
    ->value('id');

DB::table('user_roles')->insert([
    'user_id' => $userId,
    'role_id' => $roleId
]);

echo "✅ Admin role assigned\n";
