<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;

/**
 * The page-block text partials (app views/ and the starter theme) must
 * sanitize $block['html'] exactly like the page templates sanitize
 * $page->content: input-side sanitization only covers saves through the
 * admin controllers, while imported/legacy/plugin-written rows can still
 * carry markup straight into these templates.
 */
class PageBlockSanitizationTest extends TestCase
{
    private const EVIL =
        '<p>safe text</p>'
        . '<script>alert(1)</script>'
        . '<img src=x onerror=alert(2)>'
        . '<a href="javascript:alert(3)">x</a>';

    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);
        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    public function test_text_block_partial_sanitizes_html()
    {
        // 'page-blocks.text' resolves to the starter-theme partial when it
        // exists (theme view paths come first) — the one production uses.
        $html = \Flight::get('blade')->render('page-blocks.text', [
            'block' => ['type' => 'text', 'html' => self::EVIL],
        ]);

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('safe text', $html, 'ordinary markup must survive');
    }

    public function test_hero_block_partials_escape_their_fields()
    {
        // Source guard: hero partials must keep using {{ }} (escaped) for
        // every block field they render.
        foreach ([
            dirname(__DIR__, 2) . '/views/page-blocks/hero.blade.php',
            dirname(__DIR__, 2) . '/themes/pankhcmsstarter/views/page-blocks/hero.blade.php',
        ] as $file) {
            $src = file_get_contents($file);
            $this->assertStringNotContainsString('{!!', $src, "$file must not echo block fields raw");
        }
    }

    public function test_text_block_partial_source_uses_the_sanitizer()
    {
        foreach ([
            dirname(__DIR__, 2) . '/views/page-blocks/text.blade.php',
            dirname(__DIR__, 2) . '/themes/pankhcmsstarter/views/page-blocks/text.blade.php',
        ] as $file) {
            $src = file_get_contents($file);
            $this->assertStringContainsString(
                "sanitize_html((string) (\$block['html'] ?? ''))",
                $src,
                "$file must pass the block html through sanitize_html()"
            );
        }
    }
}
