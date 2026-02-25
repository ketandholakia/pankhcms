<?php

class PermissionManager
{
    public static function register(array $permissions)
    {
        try {
            if (class_exists('Illuminate\\Database\\Capsule\\Manager') && \Illuminate\Database\Capsule\Manager::schema()->hasTable('permissions')) {
                foreach ($permissions as $perm) {
                    \Illuminate\Database\Capsule\Manager::table('permissions')->insertOrIgnore(['name' => $perm]);
                }
            }
        } catch (\Throwable $e) {
        }
    }
}
