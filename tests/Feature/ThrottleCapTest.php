<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * login_throttle_enforce_cap() bounds the throttle directory: an
 * attacker hammering login/reset endpoints with rotating identifiers
 * creates files faster than the 1-day staleness prune can remove them.
 * When the file count passes the cap, the oldest files are deleted.
 * Locking and backoff behavior itself is unchanged (covered by
 * ClientIpTest's LoginThrottleTest and PasswordResetThrottleTest).
 */
class ThrottleCapTest extends TestCase
{
    private string $dir;
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = dirname(__DIR__, 2) . '/storage/cache/login_throttle';
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->created = [];
        parent::tearDown();
    }

    private function seed(string $key, int $mtime): string
    {
        $file = $this->dir . '/' . hash('sha256', 'captest|' . $key) . '.json';
        file_put_contents($file, json_encode(['first' => 0, 'count' => 0, 'lock_until' => 0, 'last' => 0]));
        touch($file, $mtime);
        $this->created[] = $file;
        return $file;
    }

    public function test_cap_removes_oldest_files_down_to_keep()
    {
        $now = time();

        $oldest = $this->seed('oldest', $now - 500);
        $older = $this->seed('older', $now - 400);
        $newest = $this->seed('newest', $now - 100);

        // 3 files, cap 2, keep 1: the two oldest must go.
        $removed = login_throttle_enforce_cap(2, 1);

        $this->assertSame(2, $removed);
        $this->assertFileDoesNotExist($oldest);
        $this->assertFileDoesNotExist($older);
        $this->assertFileExists($newest, 'the newest throttle file must survive the cap');
    }

    public function test_cap_is_a_noop_below_the_limit()
    {
        $this->seed('only-one', time());
        $this->assertSame(0, login_throttle_enforce_cap(5, 3));
    }

    public function test_register_failure_still_locks_and_backs_off_unchanged()
    {
        // Behavior contract of the write path (unchanged by the cap):
        // locks after maxAttempts, reports retry_after.
        $key = login_throttle_key('captest@example.com', '198.51.100.77');
        $file = $this->dir . '/' . $key . '.json';
        $this->created[] = $file;

        for ($i = 0; $i < 5; $i++) {
            $result = login_throttle_register_failure($key, 5, 900);
            $this->assertFalse($result['locked']);
        }

        $result = login_throttle_register_failure($key, 5, 900);
        $this->assertTrue($result['locked']);
        $this->assertGreaterThan(0, $result['retry_after']);

        login_throttle_clear($key);
    }

    public function test_prune_script_runs_the_cap_too()
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/scripts/prune-page-views.php');
        $this->assertStringContainsString(
            'login_throttle_enforce_cap()',
            $script,
            'the retention job must also bound the throttle directory'
        );

        $helpers = file_get_contents(dirname(__DIR__, 2) . '/app/Helpers/functions.php');
        $this->assertStringContainsString(
            'login_throttle_enforce_cap();',
            $helpers,
            'register_failure must enforce the cap on its opportunistic path'
        );
    }
}
