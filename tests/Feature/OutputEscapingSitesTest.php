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
        // Every template that echoes $page->content must pass it through
        // sanitize_html(). site/page.blade.php is the fallback render path
        // used by SiteController::renderPage() whenever no layout or
        // content-type template matches, so it is checked here too.
        $templates = [
            'themes/pankhcmsstarter/views/site/page.blade.php',
            'themes/pankhcmsstarter/views/templates/default.blade.php',
            'themes/pankhcmsstarter/views/templates/fullwidth.blade.php',
            'themes/pankhcmsstarter/views/templates/landing.blade.php',
            'themes/pankhcmsstarter/views/templates/sidebar.blade.php',
        ];

        foreach ($templates as $relative) {
            $template = file_get_contents(dirname(__DIR__, 2) . '/' . $relative);

            $this->assertStringNotContainsString(
                '{!! $page->content !!}',
                $template,
                "$relative must not echo page content raw"
            );
            $this->assertStringContainsString(
                '{!! sanitize_html((string) $page->content) !!}',
                $template,
                "$relative must pass page content through sanitize_html()"
            );
        }
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
     * security directives so they cannot silently disappear. The uploads
     * deny rule must also stay BEFORE the catch-all rewrite: mod_rewrite
     * applies rules in order, so if the catch-all ran first, uploads/*.php
     * would be rewritten to index.php and executed instead of 403'd.
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

        $uploadsDeny = strpos($htaccess, 'RewriteRule ^uploads/');
        $catchAll = strpos($htaccess, 'RewriteRule ^(.*)$ index.php');
        $this->assertNotFalse($uploadsDeny, 'uploads deny rule missing');
        $this->assertNotFalse($catchAll, 'front-controller catch-all rule missing');
        $this->assertLessThan(
            $catchAll,
            $uploadsDeny,
            'the uploads deny rule must run before the catch-all rewrite or it never fires'
        );
    }
}
