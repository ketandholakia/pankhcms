<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Controllers\Admin\UserController;
use Illuminate\Database\Capsule\Manager as DB;

class UserRoleChangeGuardTest extends TestCase
{
    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdUserIds as $id) {
            $user = User::find($id);
            if ($user) {
                $user->roles()->detach();
                $user->delete();
            }
        }
        $this->createdUserIds = [];
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function submitRequest(array $data): void
    {
        $request = new \flight\net\Request();
        $request->url = '/admin/users';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection($data);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);
    }

    private function location(): string
    {
        return (string) (\Flight::response()->headers()['Location'] ?? '');
    }

    private function makeManageUsersRole(string $name): Role
    {
        $role = Role::firstOrCreate(['name' => $name]);
        $perm = Permission::firstOrCreate(['name' => 'manage_users']);
        DB::table('role_permissions')->updateOrInsert([
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);
        return $role;
    }

    private function makeUser(string $prefix, Role $role): User
    {
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => $prefix . $suffix,
            'email' => $prefix . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $user->roles()->attach($role->id);
        $this->createdUserIds[] = $user->id;
        return $user;
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

    private function restoreManageUsersToAdministrator(): void
    {
        $admin = Role::where('name', 'Administrator')->first();
        $perm = Permission::where('name', 'manage_users')->first();
        DB::table('role_permissions')->updateOrInsert([
            'role_id' => $admin->id,
            'permission_id' => $perm->id,
        ]);
    }

    public function test_update_refuses_demoting_last_manage_users_user()
    {
        // Administrator temporarily loses manage_users; the deputy becomes
        // the only holder, so demoting them must be refused.
        $this->detachManageUsersFromAdministrator();

        $deputyRole = $this->makeManageUsersRole('DeputyAdmins' . bin2hex(random_bytes(2)));
        $deputy = $this->makeUser('deputy', $deputyRole);
        $viewerRole = Role::where('name', 'Viewer')->first();

        $this->submitRequest([
            'username' => $deputy->username,
            'email' => $deputy->email,
            'role_id' => (string) $viewerRole->id, // drops manage_users
        ]);

        UserController::update((int) $deputy->id);

        $this->assertStringContainsString('status=last_admin', $this->location());
        $deputy->refresh();
        $this->assertTrue($deputy->hasRole($deputyRole->name), 'role must be unchanged after a refused demotion');

        $this->restoreManageUsersToAdministrator();
    }

    public function test_update_allows_demotion_when_another_holder_remains()
    {
        $deputyRole = $this->makeManageUsersRole('DeputyAdmins' . bin2hex(random_bytes(2)));
        $deputy = $this->makeUser('deputy2', $deputyRole);
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        // Administrator still holds manage_users → two holders; demotion is fine.
        $viewerRole = Role::where('name', 'Viewer')->first();
        $this->submitRequest([
            'username' => $deputy->username,
            'email' => $deputy->email,
            'role_id' => (string) $viewerRole->id,
        ]);

        UserController::update((int) $deputy->id);

        $this->assertStringContainsString('status=updated', $this->location());
        $deputy->refresh();
        $this->assertTrue($deputy->hasRole('Viewer'));
    }

    public function test_update_refuses_invalid_role_id()
    {
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => 'badrole' . $suffix,
            'email' => 'badrole' . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $this->createdUserIds[] = $user->id;

        $this->submitRequest([
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => '999999',
        ]);

        UserController::update((int) $user->id);

        $this->assertStringContainsString('error=invalid_role', $this->location());
        $user->refresh();
        $this->assertSame(0, $user->roles()->count(), 'no role may be attached for an invalid role_id');
    }

    public function test_update_with_empty_role_id_detaches_all_roles_cleanly()
    {
        // The edit form's "-- No Role --" option submits role_id=''. That
        // must detach every role; historically it ran sync(['']), which
        // inserts a bogus role_id='' pivot row (a strict-mode SQL error on
        // MySQL/MariaDB and a corrupt row elsewhere).
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => 'norole' . $suffix,
            'email' => 'norole' . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $this->createdUserIds[] = $user->id;
        $viewerRole = Role::where('name', 'Viewer')->first();
        $user->roles()->attach($viewerRole->id);

        $this->submitRequest([
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => '', // "-- No Role --"
        ]);

        UserController::update((int) $user->id);

        $this->assertStringContainsString('status=updated', $this->location());
        $this->assertSame(
            0,
            \Illuminate\Database\Capsule\Manager::table('user_roles')->where('user_id', $user->id)->count(),
            'no pivot row (not even a role_id="" one) may remain'
        );
        $user->refresh();
        $this->assertSame(0, $user->roles()->count());
    }

    public function test_store_refuses_invalid_role_id()
    {
        $suffix = bin2hex(random_bytes(2));
        $this->submitRequest([
            'username' => 'badrolestore' . $suffix,
            'email' => 'badrolestore' . $suffix . '@example.com',
            'password' => 'Sup3r-Secret!Pass',
            'role_id' => '999999',
        ]);

        UserController::store();

        $this->assertStringContainsString('error=invalid_role', $this->location());
        $this->assertSame(0, User::where('username', 'like', 'badrolestore%')->count());
    }

    public function test_destroy_still_refuses_last_manage_users_user()
    {
        $this->detachManageUsersFromAdministrator();

        $deputyRole = $this->makeManageUsersRole('DeputyAdmins' . bin2hex(random_bytes(2)));
        $deputy = $this->makeUser('deputy3', $deputyRole);

        $_SESSION['user_id'] = User::where('email', 'admin@example.com')->value('id');

        UserController::destroy((int) $deputy->id);

        $this->assertNotNull(User::find($deputy->id), 'last manage_users user must survive delete');
        $this->restoreManageUsersToAdministrator();
    }
}
