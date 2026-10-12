<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Models\Page;

/**
 * Behavioral proof that every starter-theme template that renders
 * $page->content passes it through sanitize_html(): page content
 * containing scripts, event handlers and javascript: URLs must not
 * survive into the rendered HTML, while ordinary markup does.
 *
 * Unlike OutputEscapingSitesTest (a source-content guard), this boots the
 * real Blade engine used by Bootstrap::initBlade() and renders the real
 * templates with a malicious page fixture.
 */
class ThemeRenderSanitizationTest extends TestCase
{
    /** Content whose dangerous parts must not survive rendering. */
    private const EVIL_CONTENT =
        '<p>Safe paragraph</p>'
        . '<script>alert(1)</script>'
        . '<img src=x onerror=alert(2)>'
        . '<a href="javascript:alert(3)">evil link</a>'
        . '<strong>bold text</strong>';

    public static function setUpBeforeClass(): void
    {
        // Bootstrap the Blade engine and the view helpers exactly as the
        // application does, without running Bootstrap::init() (that would
        // replace the migrated in-memory test database).
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);

        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    private function pageFixture(): Page
    {
        $page = new Page();
        $page->id = 987654;
        $page->title = 'Sanitizer Fixture';
        $page->slug = 'sanitizer-fixture';
        $page->layout = 'default';
        $page->type = 'page';
        $page->content = self::EVIL_CONTENT;

        return $page;
    }

    private function renderTemplate(string $view): string
    {
        $blade = \Flight::get('blade');
        $this->assertNotNull($blade, 'initBlade must provide the Blade engine');

        return (string) $blade->render($view, [
            'page' => $this->pageFixture(),
            'breadcrumbs' => [],
            'blocks' => [],
            'site_name' => 'Test Site',
            'sliders' => collect(),
        ]);
    }

    /**
     * Every template that echoes $page->content must sanitize it.
     * site.page is the fallback render path in SiteController::renderPage()
     * (used whenever no specific layout/content-type template matches),
     * so it is the single most important one.
     */
    public function test_page_content_templates_render_sanitized_html()
    {
        $views = [
            'site.page',            // default fallback render path
            'templates.default',    // layout: default (never auto-selected, still ships)
            'templates.fullwidth',  // layout: fullwidth
            'templates.landing',    // layout: landing
            'templates.sidebar',    // layout: sidebar
        ];

        foreach ($views as $view) {
            $html = $this->renderTemplate($view);

            // The layout itself ships <script src=...> tags, so assert on
            // the injected payloads rather than the tag name.
            $this->assertStringNotContainsString('<script>alert', $html, "$view leaked an inline script tag");
            $this->assertStringNotContainsString('alert(1)', $html, "$view leaked script content");
            $this->assertStringNotContainsString('onerror', $html, "$view leaked an event handler");
            $this->assertStringNotContainsString('javascript:', $html, "$view leaked a javascript: URL");

            // Ordinary markup must survive sanitization.
            $this->assertStringContainsString('Safe paragraph', $html, "$view dropped safe content");
            $this->assertStringContainsString('<strong>bold text</strong>', $html, "$view mangled safe markup");
        }
    }
}
