<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;

/**
 * Phase 2: self-hosted theme — no outbound third-party requests, custom
 * stylesheet under 15KB, scroll-snap slider, dark mode with manual
 * toggle, and accessibility attributes on images/menus.
 */
class ThemePhase2Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    private function render(string $view, array $data = []): string
    {
        return (string) \Flight::get('blade')->render($view, $data);
    }

    /** Third-party hosts the old CDNs lived on. */
    private function externalHosts(): array
    {
        return [
            'cdn.jsdelivr.net',
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'unpkg.com',
            'cdn.tailwindcss.com',
        ];
    }

    public function test_theme_head_ships_no_outbound_third_party_requests()
    {
        $html = $this->render('layouts.main', ['page' => null]);

        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $html, "layout must not reference $host");
        }
        // Canonical/og URLs are absolute SELF links; third-party hosts are
        // what count as outbound requests.
        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $html);
        }
    }

    public function test_head_partial_links_only_self_hosted_assets()
    {
        $html = $this->render('partials.head', ['page' => null]);

        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $html);
        }
        $this->assertStringContainsString('css/style.css', $html, 'the self-hosted stylesheet must be linked');
        $this->assertStringContainsString('color-scheme', $html, 'color-scheme meta must declare dark support');
    }

    public function test_scripts_partial_ships_only_the_theme_bundle()
    {
        $html = $this->render('partials.scripts');

        $this->assertStringContainsString('js/app.js', $html);
        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $html);
        }
        $this->assertStringNotContainsString('bulmaCarousel', $html);
        $this->assertStringNotContainsString('lucide', $html);
    }

    public function test_stylesheet_is_self_hosted_and_under_15kb()
    {
        $path = dirname(__DIR__, 2) . '/themes/pankhcmsstarter/assets/css/style.css';
        $css = file_get_contents($path);

        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $css, 'no @import/url() to CDNs');
        }
        $this->assertLessThan(
            15 * 1024,
            strlen($css),
            'the custom stylesheet must stay under 15KB unminified'
        );
        // dark mode + slider requirements live in the stylesheet
        $this->assertStringContainsString('prefers-color-scheme: dark', $css);
        $this->assertStringContainsString('scroll-snap-type', $css);
        $this->assertStringContainsString('.theme-dark', $css);
        $this->assertStringNotContainsString('Manrope', $css, 'the Google font must be gone');
    }

    public function test_app_js_implements_slider_and_dark_mode_without_dependencies()
    {
        $js = file_get_contents(dirname(__DIR__, 2) . '/themes/pankhcmsstarter/assets/js/app.js');

        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $js);
        }
        $this->assertStringNotContainsString('bulmaCarousel', $js, 'bulma-carousel must be replaced by the scroll-snap slider');
        $this->assertStringContainsString('prefers-reduced-motion', $js, 'autoplay must respect reduced motion');
        $this->assertStringContainsString('localStorage', $js, 'dark-mode toggle must persist to localStorage');
        $this->assertStringContainsString("dataset.autoplay", $js);
        $this->assertStringContainsString('carousel-pause', $js, 'autoplay needs a pause control');
        $this->assertStringContainsString('aria-expanded', $js, 'dropdowns must be keyboard/toggle driven, not hover-only');
        $this->assertStringContainsString("key === 'Escape'", $js, 'Escape must close menus');
    }

    public function test_header_provides_the_dark_mode_toggle()
    {
        $html = $this->render('partials.header', []);

        $this->assertStringContainsString('id="theme-mode-toggle"', $html);
        $this->assertStringContainsString('aria-pressed', $html);
    }

    public function test_home_slider_and_images_render_without_third_party_assets()
    {
        $page = new \App\Models\Page();
        $page->title = 'Home';
        $page->slug = 'home';
        $page->content_json = '[]';

        $slide = new class {
            public $image_path = '/uploads/slider/one.png';
            public $title = 'Slide Two';
            public $description = 'Second slide';
            public $link = '';
            public $active = 1;
        };
        $slide2 = new class {
            public $image_path = '/uploads/slider/two.png';
            public $title = 'Slide Three';
            public $description = 'Third slide';
            public $link = '';
            public $active = 1;
        };

        $html = $this->render('site.home', [
            'page' => $page,
            'breadcrumbs' => [],
            'blocks' => [],
            'site_name' => 'Test',
            'sliders' => collect([$slide, $slide2]),
        ]);

        foreach ($this->externalHosts() as $host) {
            $this->assertStringNotContainsString($host, $html);
        }
        $this->assertStringContainsString('data-autoplay="true"', $html, 'the scroll-snap carousel must stay wired');
        $this->assertStringContainsString('loading="lazy"', $html, 'off-screen slide images must be lazy');
        $this->assertStringContainsString('decoding="async"', $html);
        $this->assertStringContainsString('width="960"', $html, 'slide images must declare dimensions');
    }
}
