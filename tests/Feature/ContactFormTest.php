<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\ContactMessage;
use flight\net\Request;
use flight\util\Collection;

class ContactFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');

        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        session_init();
        $_SESSION['contact_captcha_expected'] = '7';

        ContactMessage::query()->delete();
    }

    protected function tearDown(): void
    {
        ContactMessage::query()->delete();
        unset($_SESSION['contact_captcha_expected']);
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);

        // Reset per-IP rate limit files used by the tests
        $rateDir = dirname(__DIR__, 2) . '/storage/cache/contact_rate/';
        foreach (glob($rateDir . '*.json') ?: [] as $file) {
            @unlink($file);
        }

        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function submitRequest(array $data): void
    {
        $request = new Request();
        $request->url = '/contact';
        $request->method = 'POST';
        $request->ip = $_SERVER['REMOTE_ADDR'];
        $request->user_agent = 'phpunit';
        $request->data = new Collection($data);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);
    }

    private function callSubmit(array $data): void
    {
        $this->submitRequest($data);
        \Flight::response()->status(200);
        \App\Controllers\ContactController::submit();
    }

    public function test_happy_path_creates_message()
    {
        $this->callSubmit([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Hello',
            'message' => 'A friendly message.',
            'captcha' => '7',
            'website' => '',
        ]);

        $this->assertSame(200, \Flight::response()->status());

        $message = ContactMessage::where('email', 'jane@example.com')->first();
        $this->assertNotNull($message);
        $this->assertSame('Jane Doe', $message->name);
        $this->assertSame('203.0.113.10', $message->ip);
        $this->assertArrayNotHasKey('contact_captcha_expected', $_SESSION);
    }

    public function test_wrong_captcha_is_rejected()
    {
        $this->callSubmit([
            'name' => 'Jane Doe',
            'email' => 'jane2@example.com',
            'message' => 'Hello',
            'captcha' => '999',
            'website' => '',
        ]);

        $this->assertSame(422, \Flight::response()->status());
        $this->assertSame(0, ContactMessage::count());
        // The expected captcha stays valid for a retry
        $this->assertSame('7', $_SESSION['contact_captcha_expected'] ?? null);
    }

    public function test_missing_captcha_is_rejected()
    {
        $this->callSubmit([
            'name' => 'Jane Doe',
            'email' => 'jane3@example.com',
            'message' => 'Hello',
            'website' => '',
        ]);

        $this->assertSame(422, \Flight::response()->status());
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_honeypot_is_rejected()
    {
        $this->callSubmit([
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'spam',
            'captcha' => '7',
            'website' => 'http://spam.example',
        ]);

        $this->assertSame(422, \Flight::response()->status());
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_rate_limit_blocks_after_five_submissions()
    {
        $payload = [
            'name' => 'Flood',
            'email' => 'flood@example.com',
            'message' => 'Hello',
            'captcha' => '7',
            'website' => '',
        ];

        for ($i = 0; $i < 5; $i++) {
            $_SESSION['contact_captcha_expected'] = '7';
            $this->callSubmit($payload);
        }

        $_SESSION['contact_captcha_expected'] = '7';
        $this->callSubmit($payload);

        $this->assertSame(429, \Flight::response()->status());
        $this->assertSame(5, ContactMessage::count());
    }
}
