<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Guards for output-escaping call sites that cannot be rendered in unit
 * tests without a full Blade layout bootstrap (theme template) or a
 * Blade + media fixture harness (media picker). The sanitization
 * behavior itself is covered by HtmlSanitizerTest.
 */
class OutputEscapingSitesTest extends TestCase
{
    public function test_starter_theme_default_template_sanitizes_page_content()
    {
        $template = file_get_contents(
            dirname(__DIR__, 2) . '/themes/pankhcmsstarter/views/templates/default.blade.php'
        );

        $this->assertStringNotContainsString(
            '{!! $page->content !!}',
            $template,
            'page content must not be echoed raw'
        );
        $this->assertStringContainsString(
            '{!! sanitize_html((string) $page->content) !!}',
            $template,
            'page content must pass through sanitize_html()'
        );
    }

    public function test_media_picker_escapes_urls()
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Controllers/Admin/MediaController.php'
        );

        $this->assertStringNotContainsString(
            '" . $item->url . "',
            $controller,
            'media URLs must not be concatenated into HTML unescaped'
        );
        $this->assertSame(
            2,
            substr_count($controller, 'htmlspecialchars((string) $item->url, ENT_QUOTES'),
            'both picker output sites (img src and data-url) must escape $item->url'
        );
    }

    /**
     * Apache configuration cannot be executed in unit tests; pin the
     * security directives so they cannot silently disappear.
     */
    public function test_htaccess_disables_indexes_and_php_in_uploads()
    {
        $htaccess = file_get_contents(dirname(__DIR__, 2) . '/public/.htaccess');

        $this->assertStringContainsString('Options -Indexes', $htaccess);
        $this->assertMatchesRegularExpression(
            '/RewriteRule \^uploads\/\.\+\\\\\.php - \[NC,F,L\]/',
            $htaccess,
            'uploads PHP requests must be denied (403) by rewrite rule'
        );
    }
}
