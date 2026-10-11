<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\VisitTracker;

class VisitTrackerTest extends TestCase
{
    public function test_ipv4_last_octet_is_truncated()
    {
        $this->assertSame('203.0.113.x', VisitTracker::anonymizeIp('203.0.113.10'));
        $this->assertSame('192.168.1.x', VisitTracker::anonymizeIp('192.168.1.254'));
        $this->assertSame('1.2.3.x', VisitTracker::anonymizeIp('1.2.3.4'));
    }

    public function test_ipv6_interface_identifier_is_zeroed()
    {
        $out = VisitTracker::anonymizeIp('2001:0db8:85a3:0001:8a2e:0370:7334:0001');
        $this->assertSame('2001:db8:85a3:1::', $out);
        // The interface identifier must not survive
        $this->assertStringNotContainsString('8a2e', $out);
    }

    public function test_garbage_ips_return_empty_string()
    {
        $this->assertSame('', VisitTracker::anonymizeIp('not-an-ip'));
        $this->assertSame('', VisitTracker::anonymizeIp(''));
        $this->assertSame('', VisitTracker::anonymizeIp('999.999.999.999'));
    }
}
