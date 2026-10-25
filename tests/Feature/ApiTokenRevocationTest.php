<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\ApiToken;
use App\Controllers\Admin\UserController;
use Illuminate\Database\Capsule\Manager as DB;
use flight\net\Request;
use flight\util\Collection;

/**
 * Every path that changes a password must revoke the account's API
 * tokens: self-service change, forgot-password reset, and an
 * admin-managed change. A leaked or forgotten password should never
 * leave long-lived API credentials behind.
 */
class ApiTokenRevocationTest extends TestCase
{
    private array $userIds = [];
    private array $tokenIds = [];
    private array $resetIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200);
    }

    protected function tearDown(): void
    {
        foreach ($this->tokenIds as $id) {
            ApiToken::find($id)?->delete();
        }
        foreach ($this->userIds as $id) {
            User::find($id)?->delete();
        }
        foreach ($this->resetIds as $id) {
            DB::table('password_resets')->where('id', $id)->delete();
        }
        $this->tokenIds = [];
        $this->userIds = [];
        $this->resetIds = [];
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        \App\Core\Auth::forgetUser();
        $_POST = [];
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function makeUserWithToken(): array
    {
        $suffix = bin2hex(random_bytes(2));
        $user = User::create([
            'username' => 'tokrevoke' . $suffix,
            'email' => 'tokrevoke' . $suffix . '@example.com',
            'password' => password_hash('Sup3r-Secret!Pass', PASSWORD_DEFAULT),
        ]);
        $this->userIds[] = $user->id;

        $token = $user->apiTokens()->create([
            'name' => 'ci',
            'token' => hash('sha256', 'plain-' . $suffix),
        ]);
        $this->tokenIds[] = $token->id;

        return [$user, $token];
    }

    private function swapRequest(array $data, string $url): void
    {
        $request = new Request();
        $request->url = $url;
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

    public function test_self_service_password_change_revokes_tokens()
    {
        [$user, $token] = $this->makeUserWithToken();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['password_hash'] = $user->password;

        $this->swapRequest([
            'current_password' => 'Sup3r-Secret!Pass',
            'new_password' => 'An0ther-Secret!Pass',
            'new_password_confirmation' => 'An0ther-Secret!Pass',
        ], '/admin/profile/password');

        UserController::updatePassword();

        $this->assertNull(ApiToken::find($token->id), 'self-service password change must revoke API tokens');
        $this->assertSame(0, $user->apiTokens()->count());
    }

    public function test_forgot_password_reset_revokes_tokens()
    {
        [$user, $token] = $this->makeUserWithToken();

        $plainResetToken = bin2hex(random_bytes(16));
        $resetId = DB::table('password_resets')->insertGetId([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainResetToken),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->resetIds[] = $resetId;

        // resetPassword() reads $_POST directly (no Flight request data).
        $_POST = [
            'email' => $user->email,
            'token' => $plainResetToken,
            'password' => 'Br4nd-New!Pass',
            'password_confirmation' => 'Br4nd-New!Pass',
        ];

        \App\Controllers\AuthController::resetPassword();

        $this->assertNull(ApiToken::find($token->id), 'a password reset must revoke API tokens');
        $this->assertSame(0, $user->apiTokens()->count());
    }

    public function test_admin_managed_password_change_revokes_tokens()
    {
        [$user, $token] = $this->makeUserWithToken();

        $this->swapRequest([
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'Adm1n-Set!Pass',
        ], '/admin/users');

        UserController::update((int) $user->id);

        $this->assertNull(ApiToken::find($token->id), 'an admin-set password must revoke API tokens');
        $this->assertSame(0, $user->apiTokens()->count());
    }

    public function test_admin_edit_without_password_change_keeps_tokens()
    {
        [$user, $token] = $this->makeUserWithToken();

        $this->swapRequest([
            'username' => $user->username,
            'email' => $user->email,
            'password' => '', // keep current
        ], '/admin/users');

        UserController::update((int) $user->id);

        $this->assertNotNull(ApiToken::find($token->id), 'an edit that keeps the password must keep the tokens');
    }
}
