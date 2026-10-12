<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Controllers\Admin\UserController;
use flight\net\Request;
use flight\util\Collection;

class UserControllerPasswordPolicyTest extends TestCase
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
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function submitRequest(array $data): void
    {
        $request = new Request();
        $request->url = '/admin/users/store';
        $request->method = 'POST';
        $request->data = new Collection($data);

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

    public function test_store_rejects_weak_password()
    {
        $this->submitRequest([
            'username' => 'weakpw' . bin2hex(random_bytes(2)),
            'email' => 'weak' . bin2hex(random_bytes(2)) . '@example.com',
            'name' => 'Weak PW',
            'password' => 'short', // fails every policy rule
        ]);

        UserController::store();

        $this->assertStringContainsString('error=password_policy', $this->location());
        $this->assertStringContainsString('errors=Password', $this->location());

        $this->assertSame(
            0,
            User::where('username', 'like', 'weakpw%')->count(),
            'a weak password must not create the user'
        );
    }

    public function test_store_accepts_strong_password()
    {
        $suffix = bin2hex(random_bytes(2));
        $this->submitRequest([
            'username' => 'strongpw' . $suffix,
            'email' => 'strong' . $suffix . '@example.com',
            'name' => 'Strong PW',
            'password' => 'Sup3r-Secret!Pass',
        ]);

        UserController::store();

        $this->assertStringContainsString('/admin/users?status=created', $this->location());

        $user = User::where('username', 'strongpw' . $suffix)->first();
        $this->assertNotNull($user);
        $this->createdUserIds[] = $user->id;
        $this->assertTrue(password_verify('Sup3r-Secret!Pass', (string) $user->password));
    }

    public function test_update_rejects_weak_password()
    {
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => 'updweak' . $suffix,
            'email' => 'updweak' . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $this->createdUserIds[] = $user->id;
        $oldHash = $user->password;

        $this->submitRequest([
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'weak',
        ]);

        UserController::update((int) $user->id);

        $this->assertStringContainsString('error=password_policy', $this->location());

        $user->refresh();
        $this->assertSame($oldHash, $user->password, 'a weak password must not replace the stored hash');
    }

    public function test_update_blank_password_keeps_current()
    {
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => 'updkeep' . $suffix,
            'email' => 'updkeep' . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $this->createdUserIds[] = $user->id;
        $oldHash = $user->password;

        $this->submitRequest([
            'username' => $user->username,
            'email' => $user->email,
            'password' => '', // keep current
        ]);

        UserController::update((int) $user->id);

        $this->assertStringContainsString('status=updated', $this->location());
        $user->refresh();
        $this->assertSame($oldHash, $user->password);
    }

    public function test_update_accepts_strong_password()
    {
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => 'updstrong' . $suffix,
            'email' => 'updstrong' . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $this->createdUserIds[] = $user->id;

        $this->submitRequest([
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'An0ther-Secret!Pass',
        ]);

        UserController::update((int) $user->id);

        $this->assertStringContainsString('status=updated', $this->location());
        $user->refresh();
        $this->assertTrue(password_verify('An0ther-Secret!Pass', (string) $user->password));
    }

    public function test_role_is_attached_when_valid_role_id_given()
    {
        $suffix = bin2hex(random_bytes(2));
        $role = Role::where('name', 'Viewer')->first();
        $this->assertNotNull($role, 'canonical roles must be seeded');

        $this->submitRequest([
            'username' => 'withrole' . $suffix,
            'email' => 'withrole' . $suffix . '@example.com',
            'password' => 'Sup3r-Secret!Pass',
            'role_id' => (string) $role->id,
        ]);

        UserController::store();

        $user = User::where('username', 'withrole' . $suffix)->first();
        $this->assertNotNull($user);
        $this->createdUserIds[] = $user->id;
        $this->assertTrue($user->hasRole('Viewer'));
    }
}
