<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\SecurityHeaders;

/**
 * T6: HSTS policy is env-configurable (HSTS_MAX_AGE, HSTS_INCLUDE_SUBDOMAINS)
 * while preserving the previous default — one year, includeSubDomains —
 * so existing HTTPS deployments see byte-identical headers. HSTS is
 * domain-wide: includeSubDomains forces HTTPS on every subdomain for the
 * max-age duration, so operators with non-HTTPS sibling services need the
 * opt-out; HSTS_MAX_AGE=0 is the RFC-correct disable.
 */
class SecurityHeadersHstsConfigTest extends TestCase
{
    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['HSTS_MAX_AGE', 'HSTS_INCLUDE_SUBDOMAINS'] as $key) {
            $this->envBackup[$key] = getenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $value);
            }
        }
        unset($_SERVER['HTTPS']);
        parent::tearDown();
    }

    public function test_defaults_preserve_the_previous_header_byte_for_byte()
    {
        putenv('HSTS_MAX_AGE');
        putenv('HSTS_INCLUDE_SUBDOMAINS');
        $_SERVER['HTTPS'] = 'on';

        $headers = SecurityHeaders::headers();

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $headers['Strict-Transport-Security'],
            'with no env configuration the historical default must be preserved'
        );
    }

    public function test_max_age_env_override_is_honored()
    {
        putenv('HSTS_MAX_AGE=86400');
        $_SERVER['HTTPS'] = 'on';

        $this->assertSame(
            'max-age=86400; includeSubDomains',
            SecurityHeaders::headers()['Strict-Transport-Security']
        );
    }

    public function test_include_subdomains_can_be_disabled()
    {
        putenv('HSTS_INCLUDE_SUBDOMAINS=false');
        $_SERVER['HTTPS'] = 'on';

        $this->assertSame(
            'max-age=31536000',
            SecurityHeaders::hstsValue(),
            'operators with non-HTTPS sibling subdomains must be able to drop includeSubDomains'
        );
        $this->assertSame('max-age=31536000', SecurityHeaders::headers()['Strict-Transport-Security']);
    }

    public function test_hsts_max_age_zero_disables_the_header()
    {
        putenv('HSTS_MAX_AGE=0');
        $_SERVER['HTTPS'] = 'on';

        $this->assertNull(SecurityHeaders::hstsValue(), 'max-age 0 must disable HSTS');
        $this->assertArrayNotHasKey('Strict-Transport-Security', SecurityHeaders::headers());
    }

    public function test_non_https_requests_never_send_hsts()
    {
        putenv('HSTS_MAX_AGE=86400');
        $_SERVER['HTTPS'] = 'off';

        $this->assertArrayNotHasKey('Strict-Transport-Security', SecurityHeaders::headers());
    }

    public function test_other_security_headers_are_unaffected_by_hsts_config()
    {
        putenv('HSTS_MAX_AGE=0');
        $_SERVER['HTTPS'] = 'on';

        $headers = SecurityHeaders::headers();
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
    }
}
