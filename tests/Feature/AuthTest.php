<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Auth;
use App\Models\User;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure our test admin exists
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
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

    public function test_auth_attempt_fails_with_incorrect_credentials()
    {
        Auth::logout();
        
        $this->assertFalse(Auth::attempt('admin@example.com', 'wrongpassword'));
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::user());
    }
}
