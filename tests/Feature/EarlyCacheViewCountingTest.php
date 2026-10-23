<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Middleware\PageCache;
use App\Models\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Pre-bootstrap cache hits must still count page views: the early path
 * calls PageCache::trackEarlyHit() after the cached body is sent. The
 * insert is DB-only, stores the anonymized IP (VisitTracker::anonymizeIp),
 * carries no session id (early hits have no session cookie), and known
 * bots are skipped entirely.
 */
class EarlyCacheViewCountingTest extends TestCase
{
    /** @var int[] */
    private array $pageIds = [];
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = [
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        ];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.55';
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_COOKIE[session_name()]);

        // The production early-cache path runs in a fresh request with no
        // active session (a session cookie skips the early cache). Other
        // suite tests may have left a session open in this process; close
        // it so the tracker behaves exactly as on a cache hit.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        DB::table('page_views')->where('ip', '203.0.113.x')->delete();
    }

    protected function tearDown(): void
    {
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        $this->pageIds = [];
        DB::table('page_views')->where('ip', '203.0.113.x')->delete();

        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        parent::tearDown();
    }

    private function makePage(): Page
    {
        $page = Page::create([
            'title' => 'CountMe ' . bin2hex(random_bytes(2)),
            'slug' => 'countme-' . bin2hex(random_bytes(2)),
            'status' => 'published',
        ]);
        $this->pageIds[] = $page->id;
        return $page;
    }

    public function test_early_hit_counts_the_view_with_anonymized_ip_and_no_session()
    {
        $page = $this->makePage();
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0';

        PageCache::trackEarlyHit('/' . $page->slug);

        $row = DB::table('page_views')
            ->where('page_id', $page->id)
            ->where('ip', '203.0.113.x')
            ->first();

        $this->assertNotNull($row, 'an early cache hit must insert a page_views row');
        $this->assertSame('/' . $page->slug, $row->path);
        $this->assertNull($row->session_id, 'early hits are anonymous: no session id may be stored');
        $this->assertSame('203.0.113.x', $row->ip, 'only the anonymized IP may be persisted');
    }

    public function test_unknown_path_still_records_a_row_without_page_id()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) Firefox/128.0';

        PageCache::trackEarlyHit('/definitely-not-a-page-' . bin2hex(random_bytes(2)));

        $row = DB::table('page_views')
            ->where('ip', '203.0.113.x')
            ->whereNull('page_id')
            ->first();

        $this->assertNotNull($row, 'a cache hit for an unknown path is still a view');
    }

    public function test_bots_and_blank_user_agents_are_not_counted()
    {
        foreach ([
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'curl/8.5.0',
            'Mozilla/5.0 HeadlessChrome/126.0',
            '', // no real browser sends an empty User-Agent
        ] as $ua) {
            DB::table('page_views')->where('ip', '203.0.113.x')->delete();
            $_SERVER['HTTP_USER_AGENT'] = $ua;

            PageCache::trackEarlyHit('/whatever');

            $this->assertSame(
                0,
                DB::table('page_views')->where('ip', '203.0.113.x')->count(),
                "user agent '$ua' must not be counted"
            );
        }
    }

    public function test_is_bot_detector_covers_common_crawlers()
    {
        $this->assertTrue(PageCache::isBot('googlebot'));
        $this->assertTrue(PageCache::isBot('facebookexternalhit/1.1'));
        $this->assertTrue(PageCache::isBot(''));
        $this->assertFalse(PageCache::isBot('mozilla/5.0 (windows nt 10.0) chrome/126.0'));
        $this->assertFalse(PageCache::isBot('mozilla/5.0 (iphone; cpu iphone os 17_0) safari/605.1.15'));
    }
}
