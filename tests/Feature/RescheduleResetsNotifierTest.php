<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Models\Page;

/**
 * Rescheduling a page must re-arm the scheduled-publishing notifier:
 * PageController::update() nulls published_notified_at whenever the
 * publish date or status changes, so scripts/publish-scheduled.php
 * fires page.published (and its cache invalidation) again.
 */
class RescheduleResetsNotifierTest extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
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

    public function test_rescheduling_a_page_resets_published_notified_at()
    {
        $page = Page::create([
            'title' => 'Reschedule ' . bin2hex(random_bytes(2)),
            'slug' => 'reschedule-' . bin2hex(random_bytes(2)),
            'status' => 'published',
            'published_at' => '2026-01-01 10:00:00',
        ]);
        $this->pageIds[] = $page->id;
        $page->published_notified_at = '2026-01-01 11:00:00';
        $page->save();
        $this->assertNotNull($page->refresh()->published_notified_at, 'fixture must start as already notified');

        $this->swapRequest([
            'title' => $page->title,
            'slug' => $page->slug,
            'status' => 'published',
            'published_at' => '2026-03-04T05:06', // moved into the future
        ]);

        ob_start();
        try {
            (new \App\Controllers\Admin\PageController())->update($page->id);
        } finally {
            ob_get_clean();
        }

        $page->refresh();
        $this->assertNull(
            $page->published_notified_at,
            'a changed published_at must re-arm the scheduled-publishing notifier'
        );
        $this->assertSame('2026-03-04 05:06:00', $page->published_at->format('Y-m-d H:i:s'));
    }

    public function test_unchanged_schedule_keeps_published_notified_at()
    {
        $page = Page::create([
            'title' => 'KeepNotify ' . bin2hex(random_bytes(2)),
            'slug' => 'keepnotify-' . bin2hex(random_bytes(2)),
            'status' => 'published',
            'published_at' => '2026-01-01 10:00:00',
        ]);
        $this->pageIds[] = $page->id;
        $page->published_notified_at = '2026-01-01 11:00:00';
        $page->save();

        // Re-submitting the SAME publish date and status must not reset
        // the notifier (otherwise every unrelated edit re-notifies).
        $this->swapRequest([
            'title' => $page->title . ' (edited)',
            'slug' => $page->slug,
            'status' => 'published',
            'published_at' => '2026-01-01T10:00',
        ]);

        ob_start();
        try {
            (new \App\Controllers\Admin\PageController())->update($page->id);
        } finally {
            ob_get_clean();
        }

        $page->refresh();
        $this->assertNotNull($page->published_notified_at, 'an unchanged schedule must keep the notifier armed-off');
        $this->assertStringContainsString('(edited)', (string) $page->title, 'the unrelated edit itself must still apply');
    }
}
