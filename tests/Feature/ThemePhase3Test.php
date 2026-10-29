<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\PageController;
use App\Core\Bootstrap;
use App\Core\Theme;
use flight\net\Request;
use flight\util\Collection;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Phase 3: new page-builder blocks (sanitized + validated), theme options
 * (declared in theme.json, stored per-theme, hex-validated), and their
 * CSS-variable output in the theme head.
 */
class ThemePhase3Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
        $loadRoutes = new \ReflectionMethod(Bootstrap::class, 'loadRoutes');
        $loadRoutes->invoke(null);
        \Flight::router()->rewind();
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200)->clearBody();
    }

    protected function tearDown(): void
    {
        DB::table('settings')->where('key', 'like', 'theme_options_%')->delete();
        setting_cache_reset();
        unset($_ENV['APP_URL']);
        putenv('APP_URL');
        parent::tearDown();
    }

    private function render(string $view, array $data): string
    {
        return (string) \Flight::get('blade')->render($view, $data);
    }

    /* ---------------------------------------------- page-block rendering */

    public function test_image_block_renders_sanitized_attributes()
    {
        $html = $this->render('page-blocks.image', [
            'block' => [
                'type' => 'image',
                'src' => '/uploads/media/one.png',
                'alt' => 'An <script>alert(1)</script> image',
                'caption' => 'Caption <b>bold</b>',
            ],
        ]);

        $this->assertStringContainsString('src="/uploads/media/one.png"', $html);
        $this->assertStringContainsString('alt="An &lt;script&gt;alert(1)&lt;/script&gt; image"', $html, 'alt must be escaped, not executed');
        $this->assertStringContainsString('Caption &lt;b&gt;bold&lt;/b&gt;', $html, 'caption renders escaped markup');
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_cta_page_block_sanitizes_text_and_prefixes_internal_link()
    {
        $html = $this->render('page-blocks.cta', [
            'block' => [
                'type' => 'cta',
                'heading' => 'Get started',
                'text' => 'Body <script>alert(2)</script>text',
                'button_text' => 'Go',
                'button_link' => '/contact-us',
            ],
        ]);

        $this->assertStringContainsString('Get started', $html);
        $this->assertStringContainsString('Body text', $html);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $html);
        $this->assertStringContainsString('href="' . url('/contact-us') . '"', $html);
    }

    public function test_faq_page_block_parses_lines_and_sanitizes_answers()
    {
        $html = $this->render('page-blocks.faq', [
            'block' => [
                'type' => 'faq',
                'lines' => "What is it? | A <b>CMS</b><script>alert(3)</script>\nSecond | Answer two",
            ],
        ]);

        $this->assertSame(2, substr_count($html, '<details class="faq-item">'));
        $this->assertStringContainsString('What is it?', $html);
        $this->assertStringContainsString('A <b>CMS</b>', $html, 'answers allow simple markup');
        $this->assertStringNotContainsString('<script>alert(3)</script>', $html);
        $this->assertStringContainsString('Answer two', $html);
    }

    public function test_columns_page_block_splits_on_separator_and_sanitizes()
    {
        $html = $this->render('page-blocks.columns', [
            'block' => [
                'type' => 'columns',
                'raw' => "<p>Left <script>alert(4)</script>col</p>\n---\n<p>Right col</p>",
            ],
        ]);

        $this->assertSame(2, substr_count($html, '<div class="column '), 'two columns rendered');
        $this->assertStringContainsString('Left col', $html);
        $this->assertStringContainsString('Right col', $html);
        $this->assertStringNotContainsString('<script>alert(4)</script>', $html);
    }

    public function test_video_embed_normalizes_youtube_and_vimeo_urls()
    {
        $yt = $this->render('page-blocks.video_embed', [
            'block' => ['type' => 'video_embed', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ]);
        $this->assertStringContainsString('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', $yt);

        $short = $this->render('page-blocks.video_embed', [
            'block' => ['type' => 'video_embed', 'url' => 'https://youtu.be/dQw4w9WgXcQ'],
        ]);
        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $short);

        $vimeo = $this->render('page-blocks.video_embed', [
            'block' => ['type' => 'video_embed', 'url' => 'https://vimeo.com/123456789'],
        ]);
        $this->assertStringContainsString('src="https://player.vimeo.com/video/123456789"', $vimeo);
    }

    public function test_video_embed_ignores_non_allowlisted_providers()
    {
        $html = $this->render('page-blocks.video_embed', [
            'block' => ['type' => 'video_embed', 'url' => 'https://evil.example/embed/12345678901'],
        ]);

        $this->assertStringNotContainsString('<iframe', $html, 'non-allowlisted providers must not render an iframe');
    }

    public function test_testimonials_page_block_parses_lines()
    {
        $html = $this->render('page-blocks.testimonials', [
            'block' => ['type' => 'testimonials', 'lines' => "Great CMS | Jane | CTO\nSecond quote | Bob"],
        ]);

        $this->assertSame(2, substr_count($html, '<figure class="column is-4 testimonial">'));
        $this->assertStringContainsString('Great CMS', $html);
        $this->assertStringContainsString('Jane', $html);
        $this->assertStringContainsString('CTO', $html);
        $this->assertStringContainsString('Second quote', $html);
    }

    /* ---------------------------------------- video_embed save validation */

    private function validate(array $raw): array
    {
        $request = new Request();
        $request->url = '/admin/pages';
        $request->method = 'POST';
        $request->data = new Collection($raw);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        $method = new \ReflectionMethod(PageController::class, 'getValidatedInput');
        $method->setAccessible(true);

        return $method->invoke(new PageController(), false);
    }

    public function test_video_embed_with_disallowed_host_is_rejected_on_save()
    {
        $result = $this->validate([
            'title' => 'T',
            'content_json' => json_encode([
                ['type' => 'video_embed', 'url' => 'https://evil.example/embed/12345678901'],
            ]),
        ]);

        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('only YouTube and Vimeo', implode("\n", $result['errors']));
    }

    public function test_video_embed_with_allowed_host_passes_and_empty_url_is_rejected()
    {
        $ok = $this->validate([
            'title' => 'T',
            'content_json' => json_encode([
                ['type' => 'video_embed', 'url' => 'https://youtu.be/dQw4w9WgXcQ'],
            ]),
        ]);
        $this->assertArrayNotHasKey('errors', $ok);

        $missing = $this->validate([
            'title' => 'T',
            'content_json' => json_encode([
                ['type' => 'video_embed', 'url' => ''],
            ]),
        ]);
        $this->assertArrayHasKey('errors', $missing);
        $this->assertStringContainsString('a video URL is required', implode("\n", $missing['errors']));
    }

    /* ------------------------------------------------------- theme options */

    public function test_theme_options_merge_defaults_with_stored_overrides()
    {
        $options = Theme::options('pankhcmsstarter');
        $this->assertArrayHasKey('primary_color', $options);
        $this->assertSame('#0f6dff', $options['primary_color']['value'], 'defaults come from theme.json');

        DB::table('settings')->updateOrInsert(
            ['key' => 'theme_options_pankhcmsstarter'],
            ['value' => json_encode(['primary_color' => '#ff0000', 'footer_text' => 'Built with care'])]
        );
        setting_cache_reset();

        $options = Theme::options('pankhcmsstarter');
        $this->assertSame('#ff0000', $options['primary_color']['value']);
        $this->assertSame('Built with care', $options['footer_text']['value']);

        $this->assertSame('#ff0000', Theme::option('primary_color'));
        $this->assertNull(Theme::option('logo'), 'empty option falls back to the default (null here)');
    }

    public function test_option_validation_enforces_hex_and_numbers()
    {
        $errors = Theme::validateOptions([
            'primary_color' => '#ff0000',
            'accent_color' => 'red',
            'container_width' => '1200',
            'font_stack' => 'anything',
        ], 'pankhcmsstarter');

        $this->assertArrayHasKey('accent_color', $errors, 'non-hex color must be rejected');
        $this->assertArrayNotHasKey('primary_color', $errors);
        $this->assertArrayNotHasKey('container_width', $errors);
        $this->assertArrayNotHasKey('font_stack', $errors);

        $errors = Theme::validateOptions(['container_width' => 'wide'], 'pankhcmsstarter');
        $this->assertArrayHasKey('container_width', $errors);
    }

    public function test_head_outputs_theme_option_css_variables()
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'theme_options_pankhcmsstarter'],
            ['value' => json_encode([
                'primary_color' => '#123456',
                'accent_color' => '#654321',
                'container_width' => '1400',
            ])]
        );
        setting_cache_reset();

        $html = $this->render('partials.head', ['page' => null]);

        $this->assertStringContainsString('--theme-primary: #123456;', $html);
        $this->assertStringContainsString('--theme-accent: #654321;', $html);
        $this->assertStringContainsString('--theme-container: 1400px;', $html);
    }

    public function test_theme_options_admin_routes_exist()
    {
        // Routes may be registered more than once (test classes each load
        // the route files); assert the METHOD PAIR exists.
        $methods = [];
        foreach (\Flight::router()->getRoutes() as $route) {
            if ($route->pattern === '/admin/themes/options') {
                foreach ($route->methods as $method) {
                    $methods[$method] = true;
                }
            }
        }
        $this->assertArrayHasKey('GET', $methods, 'GET /admin/themes/options must be registered');
        $this->assertArrayHasKey('POST', $methods, 'POST /admin/themes/options must be registered');
    }
}
