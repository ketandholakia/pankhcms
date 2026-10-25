<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Models\Page;
use App\Models\ContactMessage;
use flight\net\Request;
use flight\util\Collection;

/**
 * Batch of small clean-ups:
 * - csrf_verify no longer carries a redundant header expression (the
 *   X-CSRF-TOKEN header itself still works);
 * - GlobalBlockController returns real 404s instead of findOrFail 500s;
 * - page slugs cannot shadow reserved system paths;
 * - contact messages store the anonymized IP by default, full IP only
 *   via CONTACT_STORE_FULL_IP=true;
 * - PluginManager::discoverAll skips directories with invalid or
 *   incomplete plugin.json manifests.
 */
class SmallCleanupsTest extends TestCase
{
    private array $pageIds = [];
    private array $serverBackup = [];
    private static bool $bladeReady = false;

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        if (!self::$bladeReady) {
            $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
            $initBlade->invoke(null);
            self::$bladeReady = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = [
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
            'HTTP_X_CSRF_TOKEN' => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
        ];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        \Flight::response()->status(200)->clearBody();

        // The contact rate limiter persists per-IP files across runs;
        // start each submission test with a clean budget (same as
        // ContactFormTest).
        $rateDir = dirname(__DIR__, 2) . '/storage/cache/contact_rate/';
        foreach (glob($rateDir . '*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        putenv('CONTACT_STORE_FULL_IP');
        unset($_POST['_csrf']);
        $_POST = [];
        $rateDir = dirname(__DIR__, 2) . '/storage/cache/contact_rate/';
        foreach (glob($rateDir . '*.json') ?: [] as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function test_csrf_token_via_header_still_verifies()
    {
        session_init();
        $_POST = [];
        $expected = csrf_token();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $expected;

        $this->assertTrue(csrf_verify(), 'the X-CSRF-TOKEN header must remain a valid token source');
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    public function test_global_block_update_returns_json_404_for_missing_block()
    {
        $this->swapRequest([], '/admin/global-blocks');
        (new \App\Controllers\Admin\GlobalBlockController())->update(987654321);

        $this->assertSame(404, \Flight::response()->status());
        $this->assertStringContainsString('not found', (string) \Flight::response()->getBody());
    }

    public function test_global_block_delete_returns_json_404_for_missing_block()
    {
        $this->swapRequest([], '/admin/global-blocks');
        (new \App\Controllers\Admin\GlobalBlockController())->delete(987654321);

        $this->assertSame(404, \Flight::response()->status());
    }

    public function test_global_block_edit_renders_404_view_for_missing_block()
    {
        $this->swapRequest([], '/admin/global-blocks');
        ob_start();
        try {
            (new \App\Controllers\Admin\GlobalBlockController())->edit(987654321);
        } finally {
            $html = (string) ob_get_clean();
        }

        $this->assertSame(404, \Flight::response()->status());
        $this->assertStringContainsString('Global block not found.', $html);
    }

    public function test_reserved_slugs_are_rejected_at_validation()
    {
        foreach (['admin', 'install', 'api', 'storage', 'themes', 'uploads', 'assets', 'contact', 'search', 'sitemap', 'robots', 'favicon'] as $reserved) {
            $this->swapRequest([
                'title' => 'Reserved ' . $reserved,
                'slug' => $reserved,
                'status' => 'published',
            ], '/admin/pages');

            ob_start();
            try {
                (new \App\Controllers\Admin\PageController())->store();
            } finally {
                $html = (string) ob_get_clean();
            }

            $this->assertStringContainsString(
                "is a reserved path",
                $html,
                "slug '$reserved' must be rejected with a clear message"
            );
            $this->assertSame(0, Page::where('slug', $reserved)->count(), "no page may take the slug '$reserved'");
        }
    }

    public function test_normal_slugs_are_still_accepted()
    {
        $slug = 'ordinary-page-' . bin2hex(random_bytes(2));
        $this->swapRequest([
            'title' => 'Ordinary ' . $slug,
            'slug' => $slug,
            'status' => 'published',
        ], '/admin/pages');

        ob_start();
        try {
            (new \App\Controllers\Admin\PageController())->store();
        } finally {
            ob_get_clean();
        }

        $page = Page::where('slug', $slug)->first();
        $this->assertNotNull($page, 'an ordinary slug must still be accepted');
        $this->pageIds[] = $page->id;
    }

    public function test_contact_message_stores_anonymized_ip_by_default()
    {
        ContactMessage::query()->where('ip', '203.0.113.x')->delete();
        putenv('CONTACT_STORE_FULL_IP=');
        $_POST = [];
        session_init();
        $_SESSION['contact_captcha_expected'] = '7';

        $request = new Request();
        $request->method = 'POST';
        $request->url = '/contact';
        $request->ip = '203.0.113.10';
        $request->user_agent = 'test-agent';
        $request->data = new Collection([
            'name' => 'Anon',
            'email' => 'anon@example.com',
            'subject' => 'hi',
            'message' => 'hello there',
            'captcha' => '7',
            'website' => '',
        ]);
        $this->installRequest($request);

        ob_start();
        try {
            \App\Controllers\ContactController::submit();
        } finally {
            ob_get_clean();
        }

        $message = ContactMessage::where('ip', '203.0.113.x')->first();
        $this->assertNotNull($message, 'the anonymized IP must be stored by default');
        $this->assertNull(ContactMessage::where('ip', '203.0.113.10')->first(), 'the full IP must not be stored by default');
        $message?->delete();
    }

    public function test_contact_message_full_ip_only_with_env_switch()
    {
        ContactMessage::query()->whereIn('ip', ['203.0.113.x', '203.0.113.10'])->delete();
        putenv('CONTACT_STORE_FULL_IP=true');
        $_POST = [];
        session_init();
        $_SESSION['contact_captcha_expected'] = '7';

        $request = new Request();
        $request->method = 'POST';
        $request->url = '/contact';
        $request->ip = '203.0.113.10';
        $request->user_agent = 'test-agent';
        $request->data = new Collection([
            'name' => 'Full',
            'email' => 'full@example.com',
            'subject' => 'hi',
            'message' => 'hello there',
            'captcha' => '7',
            'website' => '',
        ]);
        $this->installRequest($request);

        ob_start();
        try {
            \App\Controllers\ContactController::submit();
        } finally {
            ob_get_clean();
        }

        $message = ContactMessage::where('ip', '203.0.113.10')->first();
        $this->assertNotNull($message, 'CONTACT_STORE_FULL_IP=true must keep the full address');
        $message?->delete();
    }

    public function test_discover_all_skips_invalid_plugin_manifests()
    {
        $pluginDir = dirname(__DIR__, 2) . '/plugins/zz-invalid-manifest-test';
        @mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/plugin.json', '{ this is not json');

        try {
            $found = \PluginManager::discoverAll();
            $this->assertArrayNotHasKey('zz-invalid-manifest-test', $found);
            $this->assertArrayNotHasKey('', $found, 'a slug-less manifest must not poison the key space');
            $this->assertArrayHasKey('search', $found, 'the valid Search plugin must still be discovered');
        } finally {
            @unlink($pluginDir . '/plugin.json');
            @rmdir($pluginDir);
        }
    }

    private function swapRequest(array $data, string $url): void
    {
        $request = new Request();
        $request->url = $url;
        $request->method = 'POST';
        $request->data = new Collection($data);
        $this->installRequest($request);
    }

    private function installRequest(Request $request): void
    {
        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);
    }
}
