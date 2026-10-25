<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;
use flight\net\Request;
use flight\util\Collection;

/**
 * Admin hot-path performance:
 * - Auth::check()/user() memoize the user (with roles.permissions
 *   eager-loaded) instead of re-querying on every call;
 * - User::hasPermission loads roles.permissions at most once per
 *   instance;
 * - the media admin lists paginate 50 per page;
 * - PluginController::index uses one plugins-table query regardless of
 *   how many plugin directories exist.
 */
class AdminPerformanceTest extends TestCase
{
    private array $mediaIds = [];
    private array $serverBackup = [];

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = [
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->mediaIds as $id) {
            Media::find($id)?->delete();
        }
        $this->mediaIds = [];
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        Auth::setStatelessUser(null);
        Auth::forgetUser();
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    /* ------------------------------------------------ Auth memoization */

    public function test_auth_check_and_user_are_memoized_per_request()
    {
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['password_hash'] = $user->password;
        Auth::forgetUser();

        $connection = DB::connection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();

        $this->assertTrue(Auth::check());
        $firstBatch = count($connection->getQueryLog());
        $connection->flushQueryLog();

        // user() serves the memo, hasPermission uses the eager-loaded
        // relations: zero queries for any number of calls.
        $resolved = Auth::user();
        $resolved2 = Auth::user();
        $this->assertSame($resolved, $resolved2, 'user() must return the memoized instance');
        $this->assertSame($user->id, $resolved->id);
        $this->assertTrue($resolved->hasPermission('manage_pages'));
        $this->assertTrue($resolved->hasPermission('manage_users'));
        $memoBatch = count($connection->getQueryLog());
        $connection->flushQueryLog();

        // Every further check() costs exactly ONE single-column query
        // (live session revalidation), not a full find.
        $this->assertTrue(Auth::check());
        $this->assertTrue(Auth::check());
        $revalidationQueries = count($connection->getQueryLog());

        $connection->flushQueryLog();
        $connection->disableQueryLog();

        $this->assertLessThanOrEqual(4, $firstBatch, 'first resolution may run at most: 1 revalidation + user + roles + permissions');
        $this->assertSame(0, $memoBatch, 'user()/hasPermission must not query again');
        $this->assertSame(2, $revalidationQueries, 'each check() costs exactly one single-column revalidation query');
    }

    public function test_forget_user_after_logout_resolves_again()
    {
        $user = User::where('email', 'admin@example.com')->first();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['password_hash'] = $user->password;
        Auth::forgetUser();

        $this->assertNotNull(Auth::user());
        Auth::forgetUser();
        unset($_SESSION['user_id'], $_SESSION['password_hash']);
        $this->assertNull(Auth::user());
        $this->assertFalse(Auth::check());
    }

    /* --------------------------------------------- Media pagination */

    private function seedMedia(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $media = Media::create([
                'filename' => 'perf-' . bin2hex(random_bytes(4)) . '.png',
                'original_name' => 'PerfItem' . $i,
                'mime_type' => 'image/png',
                'size' => 1,
                'url' => '/uploads/media/perf-' . $i . '.png',
            ]);
            $this->mediaIds[] = $media->id;
        }
    }

    private function captureIndex(): string
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        try {
            (new \App\Controllers\Admin\MediaController())->index();
        } finally {
            return (string) ob_get_clean();
        }
    }

    public function test_media_index_paginates_at_fifty_per_page()
    {
        $this->seedMedia(55);

        $_GET = ['page' => '2'];
        $html = $this->captureIndex();

        $this->assertStringContainsString('Page 2 of 2', $html);
        $this->assertStringContainsString('55 items', $html);
        // The second page must show exactly the 5 newest items' delete forms.
        $this->assertSame(5, substr_count($html, '/delete"'));
    }

    public function test_media_picker_paginates_at_fifty_per_page()
    {
        $this->seedMedia(52);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => '2'];
        ob_start();
        try {
            (new \App\Controllers\Admin\MediaController())->picker();
        } finally {
            $html = (string) ob_get_clean();
        }

        $this->assertSame(2, substr_count($html, 'media-picker-select-btn'), 'page 2 of 52 items has exactly 2 buttons');
        $this->assertStringContainsString('Page 2 of 2', $html);
    }

    /* ------------------------------------------- Plugin query count */

    public function test_plugin_index_uses_a_single_plugins_query()
    {
        $connection = DB::connection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        try {
            \App\Controllers\Admin\PluginController::index();
        } finally {
            ob_get_clean();
        }

        $pluginQueries = 0;
        foreach ($connection->getQueryLog() as $q) {
            if (str_contains($q['query'], 'plugins')) {
                $pluginQueries++;
            }
        }
        $connection->flushQueryLog();
        $connection->disableQueryLog();

        $this->assertSame(
            1,
            $pluginQueries,
            'the plugins admin must load every plugin row in one query'
        );
    }
}
