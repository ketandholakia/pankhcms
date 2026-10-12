<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;

class PasswordResetThrottleTest extends TestCase
{
    private array $throttleKeys = [];

    private function throttleKeyFor(string $identifier): string
    {
        // Mirror the production key recipe so tests can clean up the
        // throttle file on disk even across separate phpunit runs.
        return login_throttle_key('password-reset', client_ip() . '|' . strtolower($identifier));
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.77';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        // Throttle files live on disk; clear this identifier's key so a
        // previous run cannot throttle this run.
        $key = $this->throttleKeyFor('someone@example.com');
        login_throttle_clear($key);
        $this->throttleKeys[] = $key;
        $key2 = $this->throttleKeyFor('first@example.com');
        login_throttle_clear($key2);
        $this->throttleKeys[] = $key2;
        $key3 = $this->throttleKeyFor('second@example.com');
        login_throttle_clear($key3);
        $this->throttleKeys[] = $key3;
    }

    protected function tearDown(): void
    {
        foreach ($this->throttleKeys as $key) {
            login_throttle_clear($key);
        }
        $this->throttleKeys = [];
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    public function test_first_ten_reset_requests_are_not_throttled()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertFalse(
                AuthController::resetLinkThrottled('someone@example.com'),
                "request " . ($i + 1) . " must pass"
            );
        }
    }

    public function test_eleventh_reset_request_is_throttled()
    {
        for ($i = 0; $i < 10; $i++) {
            AuthController::resetLinkThrottled('someone@example.com');
        }

        $this->assertTrue(
            AuthController::resetLinkThrottled('someone@example.com'),
            'the 11th request in the window must be throttled'
        );
    }

    public function test_throttle_is_per_identifier()
    {
        for ($i = 0; $i < 10; $i++) {
            AuthController::resetLinkThrottled('first@example.com');
        }

        $this->assertTrue(AuthController::resetLinkThrottled('first@example.com'));
        $this->assertFalse(
            AuthController::resetLinkThrottled('second@example.com'),
            'a different identifier must have its own budget'
        );
    }

    public function test_dummy_password_verify_accepts_bcrypt_work_factor()
    {
        // The dummy check exists purely for timing symmetry; prove it runs
        // a real bcrypt verify by measuring that a call costs a sane
        // minimum (bcrypt cost 10 ≈ >30ms on CI hardware; use a generous
        // low bound to avoid flakiness).
        $start = microtime(true);
        $method = new \ReflectionMethod(AuthController::class, 'dummyPasswordVerify');
        $method->setAccessible(true);
        $method->invoke(null, 'some-password');
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertGreaterThan(5, $elapsedMs, 'the dummy verify must run a real bcrypt check, not a no-op');
    }
}
