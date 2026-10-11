<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\HtmlSanitizer;

// For render_string_block tests (helpers are not loaded via Bootstrap in tests)
require_once dirname(__DIR__, 2) . '/app/Helpers/global_blocks.php';

class HtmlSanitizerTest extends TestCase
{
    public function test_script_tags_are_stripped()
    {
        $out = HtmlSanitizer::sanitize('<p>Hello</p><script>alert(1)</script>');
        $this->assertSame('<p>Hello</p>', $out);
    }

    public function test_script_tag_cannot_break_out_of_context()
    {
        $out = HtmlSanitizer::sanitize('<p>text</p></script><script>alert(1)</script>');
        $this->assertStringNotContainsString('<script', strtolower($out));
        $this->assertStringNotContainsString('</script>', strtolower($out));
        $this->assertStringNotContainsString('alert(1)', $out);
    }

    public function test_event_handlers_are_stripped()
    {
        $out = HtmlSanitizer::sanitize('<img src="x.png" onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('alert', $out);
    }

    public function test_javascript_urls_are_stripped()
    {
        $out = HtmlSanitizer::sanitize('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript', strtolower($out));

        $out = HtmlSanitizer::sanitize('<a href="JaVaScRiPt:alert(1)">x</a>');
        $this->assertStringNotContainsString('alert(1)', $out);

        // Obfuscated with control characters
        $out = HtmlSanitizer::sanitize('<a href="java&#9;script:alert(1)">x</a>');
        $this->assertStringNotContainsString('alert(1)', $out);
    }

    public function test_safe_urls_and_tags_survive()
    {
        $html = '<p class="intro"><a href="https://example.com" title="ok">link</a></p>'
            . '<a href="mailto:a@b.com">mail</a><a href="tel:+123">call</a>'
            . '<ul><li>item</li></ul><table><tr><td>cell</td></tr></table>';
        $out = HtmlSanitizer::sanitize($html);
        $this->assertStringContainsString('href="https://example.com"', $out);
        $this->assertStringContainsString('mailto:a@b.com', $out);
        $this->assertStringContainsString('tel:+123', $out);
        $this->assertStringContainsString('<li>item</li>', $out);
        $this->assertStringContainsString('cell', $out);
    }

    public function test_style_and_iframe_are_removed_with_content()
    {
        $out = HtmlSanitizer::sanitize('<style>body{display:none}</style><p>x</p><iframe src="https://evil.com"></iframe>');
        $this->assertStringNotContainsString('display:none', $out);
        $this->assertStringNotContainsString('iframe', strtolower($out));
        $this->assertStringContainsString('<p>x</p>', $out);
    }

    public function test_empty_input_returns_empty_string()
    {
        $this->assertSame('', HtmlSanitizer::sanitize(''));
    }

    public function test_render_string_block_supports_setting_placeholder_and_sanitizes()
    {
        \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(
            ['key' => 'test_block_setting'],
            ['value' => 'WidgetCorp <b>bold</b>']
        );
        setting_cache_reset(); // the snapshot may predate this key

        $out = render_string_block('<p>Welcome to {{ setting(\'test_block_setting\') }}</p><script>alert(1)</script>');
        // Placeholder substituted, HTML-escaped
        $this->assertStringContainsString('WidgetCorp &lt;b&gt;bold&lt;/b&gt;', $out);
        $this->assertStringContainsString('<p>', $out);
        $this->assertStringNotContainsString('<script', strtolower($out));

        \Illuminate\Database\Capsule\Manager::table('settings')->where('key', 'test_block_setting')->delete();
        setting_cache_reset();
    }

    public function test_render_string_block_does_not_execute_blade_directives()
    {
        $out = render_string_block('@php echo "PWNED"; @endphp <p>ok</p>{{ setting(\'nonexistent_key_xyz\') }}');
        $this->assertStringNotContainsString('<?php', $out);
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function test_sanitize_blocks_json_strips_script_and_handlers()
    {
        $json = json_encode([
            ['type' => 'text', 'html' => '<p>hi</p><script>alert(1)</script>'],
            ['type' => 'text', 'data' => ['text' => '<img src="x.png" onerror="alert(2)">']],
            ['type' => 'hero', 'title' => '<b>Title</b>'],
        ]);

        $out = HtmlSanitizer::sanitizeBlocksJson($json);
        $decoded = json_decode($out, true);

        $this->assertIsArray($decoded);
        $this->assertSame('<p>hi</p>', $decoded[0]['html']);
        $this->assertSame('<img src="x.png">', $decoded[1]['data']['text']);
        // Non rich-text fields are untouched
        $this->assertSame('<b>Title</b>', $decoded[2]['title']);
        $this->assertStringNotContainsString('alert(1)', $out);
        $this->assertStringNotContainsString('onerror', $out);
    }

    public function test_sanitize_blocks_json_escapes_script_breakout()
    {
        // Script markup embedded in a block is stripped outright...
        $json = json_encode([
            ['type' => 'text', 'html' => 'hello </script><script>alert(1)</script>'],
        ]);
        $out = HtmlSanitizer::sanitizeBlocksJson($json);
        $this->assertSame('hello', json_decode($out, true)[0]['html']);
        $this->assertStringNotContainsString('<script', strtolower($out));

        // ...and a literal "</script>" that arrives as escaped text survives
        // only as an entity. The stored JSON never contains a raw
        // "</script>" sequence, so embedding it in a <script> element
        // cannot terminate the script context early.
        $json = json_encode([
            ['type' => 'text', 'html' => '<p>note</p><code>&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;</code>'],
        ]);
        $out = HtmlSanitizer::sanitizeBlocksJson($json);
        // No raw "</script>" sequence can terminate a surrounding script
        // element: the < is stored entity-escaped (&lt;) and the / is
        // JSON-escaped (\/) — belt and braces.
        $this->assertStringNotContainsString('</script>', $out);
        $this->assertStringNotContainsString('<script', strtolower($out));
        $this->assertStringContainsString('&lt;\/script&gt;', $out);
        $this->assertStringContainsString('&lt;/script&gt;', json_decode($out, true)[0]['html']);
    }

    public function test_sanitize_blocks_json_rejects_invalid_json()
    {
        $this->assertSame('[]', HtmlSanitizer::sanitizeBlocksJson('not json at all'));
    }
}
