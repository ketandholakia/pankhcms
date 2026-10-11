<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\UserController;
use Illuminate\Database\Capsule\Manager as DB;

class RoleUserGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');

        // A throwaway role that holds manage_users
        $role = Role::firstOrCreate(['name' => 'UserManagers']);
        $perm = Permission::firstOrCreate(['name' => 'manage_users']);
        DB::table('role_permissions')->updateOrInsert([
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);
    }

    protected function tearDown(): void
    {
        // Restore Administrator's manage_users grant and clean up
        $admin = Role::where('name', 'Administrator')->first();
        $perm = Permission::where('name', 'manage_users')->first();
        if ($admin && $perm) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $admin->id,
                'permission_id' => $perm->id,
            ]);
        }

        $role = Role::where('name', 'UserManagers')->first();
        if ($role) {
            DB::table('user_roles')->where('role_id', $role->id)->delete();
            DB::table('role_permissions')->where('role_id', $role->id)->delete();
            $role->delete();
        }

        User::where('email', 'deputy@example.com')->delete();
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function detachManageUsersFromAdministrator(): void
    {
        $admin = Role::where('name', 'Administrator')->first();
        $perm = Permission::where('name', 'manage_users')->first();
        DB::table('role_permissions')
            ->where('role_id', $admin->id)
            ->where('permission_id', $perm->id)
            ->delete();
    }

    public function test_cannot_delete_last_role_holding_manage_users()
    {
        $this->detachManageUsersFromAdministrator();

        $role = Role::where('name', 'UserManagers')->first();
        RoleController::destroy($role->id);

        $this->assertNotNull(Role::where('name', 'UserManagers')->first(), 'last manage_users role must survive');
    }

    public function test_can_delete_manage_users_role_when_another_exists()
    {
        // Administrator still holds manage_users, so UserManagers can go.
        $role = Role::where('name', 'UserManagers')->first();
        RoleController::destroy($role->id);

        $this->assertNull(Role::where('name', 'UserManagers')->first());
    }

    public function test_cannot_delete_last_admin_user()
    {
        // Administrator temporarily loses manage_users; the deputy becomes
        // the only admin-capable user.
        $this->detachManageUsersFromAdministrator();

        $admin = User::where('email', 'admin@example.com')->first();
        $deputy = User::create([
            'username' => 'deputy',
            'name' => 'Deputy Admin',
            'email' => 'deputy@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);
        $deputy->roles()->attach(Role::where('name', 'UserManagers')->first()->id);

        $_SESSION['user_id'] = $admin->id;

        UserController::destroy($deputy->id);
        $this->assertNotNull(User::find($deputy->id), 'last admin user must survive');
    }

    public function test_can_delete_admin_user_when_others_remain()
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $deputy = User::create([
            'username' => 'deputy2',
            'name' => 'Deputy Admin 2',
            'email' => 'deputy@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);
        $deputy->roles()->attach(Role::where('name', 'UserManagers')->first()->id);

        $_SESSION['user_id'] = $admin->id;

        // Two manage_users users exist; deleting the deputy is fine.
        UserController::destroy($deputy->id);
        $this->assertNull(User::find($deputy->id));
    }
}
