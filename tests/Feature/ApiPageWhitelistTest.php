<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\Page;
use App\Controllers\Api\PageController;
use flight\net\Request;
use flight\util\Collection;

class ApiPageWhitelistTest extends TestCase
{
    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');

        $suffix = bin2hex(random_bytes(3));
        $this->page = Page::create([
            'title' => 'API Whitelist ' . $suffix,
            'slug' => 'api-whitelist-' . $suffix,
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->subDay(),
            'seo_title' => 'Secret SEO Title',
            'noindex' => 1, // internal column — must never be exposed
            'type' => 'page',
        ]);

        $request = new Request();
        $request->url = '/api/v1/pages/' . $this->page->slug;
        $request->method = 'GET';
        $request->query = new Collection([]);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        \Flight::response()->clearBody();
        \Flight::response()->status(200);
    }

    protected function tearDown(): void
    {
        $this->page->delete();
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    private function responseData(): array
    {
        $body = \Flight::response()->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded, 'response must be valid JSON');
        return $decoded;
    }

    public function test_show_returns_only_whitelisted_fields()
    {
        PageController::show($this->page->slug);

        $decoded = $this->responseData();
        $this->assertArrayHasKey('data', $decoded);

        $actualKeys = array_keys($decoded['data']);
        sort($actualKeys);
        $expectedKeys = PageController::PUBLIC_FIELDS;
        sort($expectedKeys);

        $this->assertSame($expectedKeys, $actualKeys, 'the API must expose exactly the whitelisted fields');
    }

    public function test_show_does_not_leak_internal_columns()
    {
        PageController::show($this->page->slug);

        $decoded = $this->responseData();

        foreach (['noindex', 'user_id', 'status', 'layout', 'featured_image'] as $internal) {
            $this->assertArrayNotHasKey($internal, $decoded['data'], "$internal must not be exposed");
        }

        // Whitelisted content is present and correct.
        $this->assertSame($this->page->id, $decoded['data']['id']);
        $this->assertSame('Secret SEO Title', $decoded['data']['seo_title']);
    }

    public function test_show_returns_404_for_missing_slug()
    {
        \Flight::response()->status(200);
        PageController::show('no-such-page-' . bin2hex(random_bytes(4)));

        $this->assertSame(404, \Flight::response()->status());
    }
}
