<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class ClientIpTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP'],
            $_SERVER['HTTP_CF_CONNECTING_IP']
        );
        putenv('TRUSTED_PROXIES');
        putenv('TRUSTED_PROXY_HEADER');
        parent::tearDown();
    }

    public function test_forwards_ignored_without_trusted_proxies()
    {
        putenv('TRUSTED_PROXIES');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 5.6.7.8';
        $_SERVER['HTTP_X_REAL_IP'] = '9.9.9.9';

        $this->assertSame('203.0.113.50', client_ip());
    }

    public function test_forwards_ignored_when_remote_addr_not_a_proxy()
    {
        putenv('TRUSTED_PROXIES=10.0.0.1,10.0.0.0/24');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';

        $this->assertSame('203.0.113.50', client_ip());
    }

    public function test_rightmost_untrusted_address_is_used()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24,127.0.0.1');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        // Chain: client, proxy1, proxy2(remote). 10.0.0.2 is trusted, 1.2.3.4 is not.
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 10.0.0.2';

        $this->assertSame('1.2.3.4', client_ip());
    }

    public function test_spoofed_leftmost_value_is_ignored()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        // Attacker injects a fake chain; the rightmost untrusted hop wins.
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6, 1.2.3.4, 10.0.0.2';

        $this->assertSame('1.2.3.4', client_ip());
    }

    public function test_all_trusted_chain_falls_back_to_remote_addr()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.2, 10.0.0.3';

        $this->assertSame('10.0.0.7', client_ip());
    }

    public function test_invalid_forwarded_entries_are_skipped()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-an-ip, 1.2.3.4';

        $this->assertSame('1.2.3.4', client_ip());
    }

    public function test_client_supplied_x_real_ip_is_ignored_unless_configured()
    {
        // Default header is X-Forwarded-For: even behind a trusted proxy,
        // a client-injected X-Real-IP must never be read.
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        putenv('TRUSTED_PROXY_HEADER=X-Forwarded-For');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $_SERVER['HTTP_X_REAL_IP'] = '6.6.6.6'; // attacker-supplied

        $this->assertSame('1.2.3.4', client_ip());
    }

    public function test_configured_x_real_ip_is_used()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        putenv('TRUSTED_PROXY_HEADER=X-Real-IP');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        $_SERVER['HTTP_X_REAL_IP'] = '1.2.3.4';
        // XFF present but NOT the configured header: ignored.
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6';

        $this->assertSame('1.2.3.4', client_ip());
    }

    public function test_configured_cf_connecting_ip_is_used()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        putenv('TRUSTED_PROXY_HEADER=CF-Connecting-IP');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6';

        $this->assertSame('198.51.100.9', client_ip());
    }

    public function test_single_value_header_requires_trusted_proxy()
    {
        // Same header, but REMOTE_ADDR is not a trusted proxy: ignored.
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        putenv('TRUSTED_PROXY_HEADER=X-Real-IP');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
        $_SERVER['HTTP_X_REAL_IP'] = '1.2.3.4';

        $this->assertSame('203.0.113.50', client_ip());
    }

    public function test_single_value_header_with_proxy_ip_or_garbage_falls_back()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        putenv('TRUSTED_PROXY_HEADER=X-Real-IP');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';

        // Another trusted proxy as the value: not the client.
        $_SERVER['HTTP_X_REAL_IP'] = '10.0.0.3';
        $this->assertSame('10.0.0.7', client_ip());

        // Garbage value.
        $_SERVER['HTTP_X_REAL_IP'] = 'not-an-ip';
        $this->assertSame('10.0.0.7', client_ip());
    }

    public function test_unknown_configured_header_falls_back_to_remote_addr()
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/24');
        putenv('TRUSTED_PROXY_HEADER=X-Made-Up-Header');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';

        $this->assertSame('10.0.0.7', client_ip());
    }
}

class LoginThrottleTest extends TestCase
{
    private array $keys = [];

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            login_throttle_clear($key);
        }
        $this->keys = [];
        parent::tearDown();
    }

    public function test_ip_throttle_locks_after_five_failures()
    {
        $key = login_throttle_key('victim@example.com', '198.51.100.10');
        $this->keys[] = $key;

        for ($i = 0; $i < 5; $i++) {
            $result = login_throttle_register_failure($key);
            $this->assertFalse($result['locked']);
        }

        $result = login_throttle_register_failure($key);
        $this->assertTrue($result['locked']);
        $this->assertGreaterThan(0, $result['retry_after']);

        $check = login_throttle_check($key);
        $this->assertFalse($check['allowed']);
    }

    public function test_account_throttle_ignores_rotating_ips()
    {
        $accountKey = login_throttle_key_account('victim2@example.com');
        $this->keys[] = $accountKey;

        // 20 failures from 20 different IPs: the per-IP throttle resets
        // every time, but the account key keeps counting.
        for ($i = 0; $i < 20; $i++) {
            $ipKey = login_throttle_key('victim2@example.com', "198.51.100.$i");
            $this->keys[] = $ipKey;
            login_throttle_register_failure($ipKey);
            $this->assertTrue(login_throttle_check($ipKey)['allowed'], 'per-IP should not lock before 5');

            $result = login_throttle_register_failure($accountKey, 20);
            $this->assertFalse($result['locked'], "account should not lock at attempt " . ($i + 1));
        }

        $result = login_throttle_register_failure($accountKey, 20);
        $this->assertTrue($result['locked']);
        $this->assertTrue(login_throttle_check($accountKey)['allowed'] === false);
    }

    public function test_prune_removes_stale_files()
    {
        $key = login_throttle_key('prune@example.com', '198.51.100.99');
        $this->keys[] = $key;
        login_throttle_register_failure($key);

        $file = dirname(__DIR__, 2) . '/storage/cache/login_throttle/' . $key . '.json';
        $this->assertFileExists($file);

        // Backdate the file and prune
        touch($file, time() - 172800);
        login_throttle_prune(86400);

        $this->assertFileDoesNotExist($file);
    }
}
