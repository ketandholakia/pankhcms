<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\Page;
use App\Core\Auth;
use App\Models\User;

class PageSchedulingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure test user exists
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'username' => 'testadmin',
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
            ]);
        }

        // Clean up previous test pages (though tests run in memory sqlite)
        Page::where('slug', 'like', 'test-schedule-%')->delete();
    }

    public function test_future_published_at_is_not_visible()
    {
        $page = Page::create([
            'title' => 'Future Page',
            'slug' => 'test-schedule-future',
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->addDays(1),
        ]);

        $visible = Page::where('id', $page->id)->visible()->first();
        $this->assertNull($visible, 'Future published pages should not be visible');
    }

    public function test_past_published_at_is_visible()
    {
        $page = Page::create([
            'title' => 'Past Page',
            'slug' => 'test-schedule-past',
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now()->subDays(1),
        ]);

        $visible = Page::where('id', $page->id)->visible()->first();
        $this->assertNotNull($visible, 'Past published pages should be visible');
    }

    public function test_null_published_at_is_visible()
    {
        $page = Page::create([
            'title' => 'Null Publish Page',
            'slug' => 'test-schedule-null',
            'status' => 'published',
            'published_at' => null,
        ]);

        $visible = Page::where('id', $page->id)->visible()->first();
        $this->assertNotNull($visible, 'Pages with null published_at should be visible');
    }

    public function test_draft_is_never_visible_regardless_of_published_at()
    {
        $page1 = Page::create([
            'title' => 'Draft Past',
            'slug' => 'test-schedule-draft-past',
            'status' => 'draft',
            'published_at' => \Illuminate\Support\Carbon::now()->subDays(1),
        ]);

        $page2 = Page::create([
            'title' => 'Draft Null',
            'slug' => 'test-schedule-draft-null',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->assertNull(Page::where('id', $page1->id)->visible()->first());
        $this->assertNull(Page::where('id', $page2->id)->visible()->first());
    }

    public function test_admin_preview_on_unpublished_page()
    {
        $page = Page::create([
            'title' => 'Draft Page',
            'slug' => 'test-schedule-draft',
            'status' => 'draft',
        ]);

        // Mock Flight request
        if (!\Flight::request()) {
            \Flight::set('request', new \flight\net\Request());
        }
        
        \Flight::request()->query->preview = 1;
        Auth::attempt('admin@example.com', 'password');

        $method = new \ReflectionMethod(\App\Controllers\Site\SiteController::class, 'getPageQuery');
        $method->setAccessible(true);
        $query = $method->invoke(null);
        
        $this->assertNotNull($query->where('id', $page->id)->first(), 'Admin with preview=1 should see draft');

        // Anonymous
        Auth::logout();
        $query2 = $method->invoke(null);
        $this->assertNull($query2->where('id', $page->id)->first(), 'Anonymous with preview=1 should NOT see draft');
        
        // Reset
        \Flight::request()->query->preview = null;
    }
}
