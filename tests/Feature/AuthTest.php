<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Auth;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure our test admin exists
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'username' => 'testadmin',
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
            ]);
        }
    }

    public function test_auth_attempt_succeeds_with_correct_credentials()
    {
        $this->assertTrue(Auth::attempt('admin@example.com', 'password'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('admin@example.com', Auth::user()->email);
        
        Auth::logout();
        $this->assertFalse(Auth::check());
    }

    public function test_auth_attempt_succeeds_with_username_identifier()
    {
        Auth::logout();

        $this->assertTrue(Auth::attempt('testadmin', 'password'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('testadmin', Auth::user()->username);

        Auth::logout();
        $this->assertFalse(Auth::check());
    }

    public function test_auth_attempt_fails_with_incorrect_credentials()
    {
        Auth::logout();
        
        $this->assertFalse(Auth::attempt('admin@example.com', 'wrongpassword'));
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::user());
    }

    public function test_password_reset_token_can_be_issued_for_user()
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $method = new \ReflectionMethod(\App\Controllers\AuthController::class, 'issuePasswordResetToken');
        $method->setAccessible(true);

        $plainToken = $method->invoke(null, $user->id);

        $record = Capsule::table('password_resets')->where('user_id', $user->id)->first();

        $this->assertNotEmpty($plainToken);
        $this->assertNotNull($record);
        $this->assertSame(hash('sha256', $plainToken), $record->token);
    }

    public function test_password_reset_lookup_rejects_expired_tokens()
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $plainToken = bin2hex(random_bytes(16));

        Capsule::table('password_resets')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => date('Y-m-d H:i:s', time() - 60),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $method = new \ReflectionMethod(\App\Controllers\AuthController::class, 'findValidPasswordReset');
        $method->setAccessible(true);

        $result = $method->invoke(null, $user->email, $plainToken);

        $this->assertNull($result);
    }
}
