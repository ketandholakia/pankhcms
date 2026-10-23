<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Auth;
use App\Models\User;
use App\Models\Role;
use App\Middleware\RequirePermission;
use Illuminate\Database\Capsule\Manager as DB;

class PermissionMiddlewareTest extends TestCase
{
    protected User $limitedUser;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1'); // prevents Flight::halt from exiting

        // A role with no permissions and a user holding only that role
        $role = Role::firstOrCreate(['name' => 'Visitor']);
        DB::table('role_permissions')->where('role_id', $role->id)->delete();

        $this->limitedUser = User::create([
            'username' => 'limiteduser',
            'name' => 'Limited User',
            'email' => 'limited@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);
        $this->limitedUser->roles()->attach($role->id);
    }

    protected function tearDown(): void
    {
        DB::table('role_permissions')->where('user_id', 0)->delete();
        $this->limitedUser->roles()->detach();
        $this->limitedUser->delete();
        Role::where('name', 'Visitor')->delete();
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        Auth::forgetUser();
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    public function test_user_without_permission_is_denied()
    {
        $this->assertFalse($this->limitedUser->hasPermission('manage_backups'));
        $this->assertFalse($this->limitedUser->hasPermission('manage_messages'));

        $_SESSION['user_id'] = $this->limitedUser->id;

        $middleware = new RequirePermission('manage_backups');
        $middleware->before();

        $this->assertSame(403, \Flight::response()->status());
    }

    public function test_user_with_permission_passes()
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasPermission('manage_backups'));

        $_SESSION['user_id'] = $admin->id;

        // Reset the shared response (a prior test may have set 403)
        \Flight::response()->status(200);

        $middleware = new RequirePermission('manage_backups');
        $middleware->before(); // must not halt

        $this->assertNotSame(403, \Flight::response()->status());

        unset($_SESSION['user_id']);
    }

    public function test_administrator_role_has_new_permissions_seeded()
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $this->assertNotNull($adminRole);
        $this->assertTrue(
            $adminRole->permissions->contains('name', 'manage_backups'),
            'manage_backups should be granted to Administrator by migrations'
        );
        $this->assertTrue(
            $adminRole->permissions->contains('name', 'manage_messages'),
            'manage_messages should be granted to Administrator by migrations'
        );
    }
}
