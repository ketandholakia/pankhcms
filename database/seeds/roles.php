<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('seed_default_roles_and_permissions')) {
    /**
     * Canonical role & permission seeding, shared by the migrations and
     * the installer so both always agree. Idempotent; safe to re-run.
     */
    function seed_default_roles_and_permissions(): void
    {
        // Reconcile legacy lowercase names from older installers.
        $legacyMap = [
            'admin' => 'Administrator',
            'editor' => 'Editor',
            'author' => 'Author',
            'viewer' => 'Viewer',
        ];
        foreach ($legacyMap as $legacy => $canonical) {
            $legacyExists = Capsule::table('roles')->where('name', $legacy)->exists();
            $canonicalExists = Capsule::table('roles')->where('name', $canonical)->exists();
            if ($legacyExists && !$canonicalExists) {
                Capsule::table('roles')->where('name', $legacy)->update(['name' => $canonical]);
            } elseif ($legacyExists) {
                // Both exist: fold the legacy role into the canonical one.
                $legacyId = Capsule::table('roles')->where('name', $legacy)->value('id');
                $canonicalId = Capsule::table('roles')->where('name', $canonical)->value('id');
                if ($legacyId && $canonicalId) {
                    $userRows = Capsule::table('user_roles')->where('role_id', $legacyId)->get();
                    foreach ($userRows as $row) {
                        Capsule::table('user_roles')->updateOrInsert([
                            'user_id' => $row->user_id,
                            'role_id' => $canonicalId,
                        ]);
                    }
                    Capsule::table('user_roles')->where('role_id', $legacyId)->delete();
                    Capsule::table('roles')->where('id', $legacyId)->delete();
                }
            }
        }

        foreach (['Administrator', 'Editor', 'Author', 'Viewer'] as $role) {
            Capsule::table('roles')->updateOrInsert(['name' => $role]);
        }

        $allPermissions = [
            'manage_settings', 'manage_pages', 'manage_taxonomies', 'manage_menus',
            'manage_media', 'manage_content_types', 'manage_blocks', 'manage_templates',
            'manage_themes', 'manage_plugins', 'manage_users', 'manage_backups',
            'manage_messages',
        ];
        foreach ($allPermissions as $perm) {
            Capsule::table('permissions')->updateOrInsert(['name' => $perm]);
        }

        $rolePermissions = [
            'Administrator' => $allPermissions,
            'Editor' => [
                'manage_pages', 'manage_taxonomies', 'manage_menus', 'manage_media',
                'manage_blocks', 'manage_templates', 'manage_messages',
            ],
            'Author' => ['manage_pages', 'manage_media'],
            'Viewer' => [],
        ];

        foreach ($rolePermissions as $roleName => $permNames) {
            $roleId = Capsule::table('roles')->where('name', $roleName)->value('id');
            if (!$roleId) {
                continue;
            }
            foreach ($permNames as $permName) {
                $permId = Capsule::table('permissions')->where('name', $permName)->value('id');
                if ($permId) {
                    Capsule::table('role_permissions')->updateOrInsert([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        // Never lock out the first user on existing sites.
        $adminRoleId = Capsule::table('roles')->where('name', 'Administrator')->value('id');
        $firstUser = Capsule::table('users')->orderBy('id')->first();
        if ($adminRoleId && $firstUser) {
            Capsule::table('user_roles')->updateOrInsert([
                'user_id' => $firstUser->id,
                'role_id' => $adminRoleId,
            ]);
        }
    }
}
