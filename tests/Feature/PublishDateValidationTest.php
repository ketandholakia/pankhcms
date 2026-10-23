<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Models\Page;

/**
 * Timezone application and strict publish-date validation:
 * - Bootstrap::applyTimezone() applies the `timezone` setting (UTC on
 *   invalid values) so Carbon::now() and strtotime() agree;
 * - PageController rejects a submitted published_at that is not a real
 *   date/time instead of letting strtotime() store 1970-01-01;
 * - a valid datetime-local value is interpreted in the configured
 *   timezone and never silently replaced by now().
 */
class PublishDateValidationTest extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];
    private string $previousTimezone;

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousTimezone = date_default_timezone_get();
        \Flight::response()->status(200);
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
        date_default_timezone_set($this->previousTimezone);
        parent::tearDown();
    }

    private function swapRequest(array $data): void
    {
        $request = new \flight\net\Request();
        $request->url = '/admin/pages';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection($data);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);
    }

    public function test_apply_timezone_uses_setting_and_falls_back_to_utc()
    {
        $settings = \Illuminate\Database\Capsule\Manager::table('settings');
        $settings->updateOrInsert(['key' => 'timezone'], ['value' => 'Asia/Kolkata']);
        setting_cache_reset();

        Bootstrap::applyTimezone();
        $this->assertSame('Asia/Kolkata', date_default_timezone_get());

        // Invalid value must fall back to UTC, not fatal.
        $settings->updateOrInsert(['key' => 'timezone'], ['value' => 'Not/AZone']);
        setting_cache_reset();

        Bootstrap::applyTimezone();
        $this->assertSame('UTC', date_default_timezone_get());

        $settings->updateOrInsert(['key' => 'timezone'], ['value' => 'Asia/Kolkata']);
        setting_cache_reset();
    }

    public function test_invalid_published_at_is_a_validation_error_not_1970()
    {
        $title = 'BadDate ' . bin2hex(random_bytes(2));
        $this->swapRequest([
            'title' => $title,
            'slug' => 'bad-date-' . bin2hex(random_bytes(2)),
            'status' => 'published',
            'published_at' => 'not-a-date',
        ]);

        ob_start();
        try {
            (new \App\Controllers\Admin\PageController())->store();
        } finally {
            $output = (string) ob_get_clean();
        }

        $this->assertStringContainsString('not a valid date and time', $output, 'the error view must explain the bad date');
        $this->assertSame(0, Page::where('title', $title)->count(), 'no page may be created from an invalid publish date');
    }

    public function test_valid_datetime_local_value_is_kept_not_replaced_by_now()
    {
        date_default_timezone_set('Asia/Kolkata');
        $title = 'GoodDate ' . bin2hex(random_bytes(2));
        $slug = 'good-date-' . bin2hex(random_bytes(2));
        $this->swapRequest([
            'title' => $title,
            'slug' => $slug,
            'status' => 'published',
            'published_at' => '2026-01-02T03:04',
        ]);

        ob_start();
        try {
            (new \App\Controllers\Admin\PageController())->store();
        } finally {
            ob_get_clean();
        }

        $page = Page::where('slug', $slug)->first();
        $this->assertNotNull($page, 'the page must be created');
        $this->pageIds[] = $page->id;

        // The submitted wall-clock time is stored as-is, interpreted in
        // the configured timezone — and never replaced by now().
        $this->assertSame(
            '2026-01-02 03:04:00',
            $page->published_at->format('Y-m-d H:i:s'),
            'a valid submitted publish date must be stored exactly, not reset to now()'
        );
    }

    public function test_parser_is_strict_about_impossible_dates()
    {
        $this->assertNull(\App\Controllers\Admin\PageController::parsePublishedAt('2026-02-30T10:00'));
        $this->assertNull(\App\Controllers\Admin\PageController::parsePublishedAt('2026-13-01 10:00'));
        $this->assertNull(\App\Controllers\Admin\PageController::parsePublishedAt('garbage'));
        $this->assertNull(\App\Controllers\Admin\PageController::parsePublishedAt(''));

        $this->assertSame(
            '2026-01-02 03:04:00',
            \App\Controllers\Admin\PageController::parsePublishedAt('2026-01-02T03:04')
        );
        $this->assertSame(
            '2026-01-02 03:04:00',
            \App\Controllers\Admin\PageController::parsePublishedAt('2026-01-02 03:04')
        );
    }
}
