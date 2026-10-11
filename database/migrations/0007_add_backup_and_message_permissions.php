<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Register the manage_backups and manage_messages permissions and grant
// them to the Administrator role so backup/message admin pages are no
// longer login-only.
$newPermissions = ['manage_backups', 'manage_messages'];

foreach ($newPermissions as $perm) {
    Capsule::table('permissions')->updateOrInsert(['name' => $perm]);
}

$adminRole = Capsule::table('roles')->where('name', 'Administrator')->first();
if ($adminRole) {
    $permIds = Capsule::table('permissions')->whereIn('name', $newPermissions)->pluck('id')->toArray();
    foreach ($permIds as $pid) {
        Capsule::table('role_permissions')->updateOrInsert([
            'role_id' => $adminRole->id,
            'permission_id' => $pid,
        ]);
    }
}
