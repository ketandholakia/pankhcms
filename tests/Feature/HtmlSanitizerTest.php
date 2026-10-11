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

        $out = render_string_block('<p>Welcome to {{ setting(\'test_block_setting\') }}</p><script>alert(1)</script>');
        // Placeholder substituted, HTML-escaped
        $this->assertStringContainsString('WidgetCorp &lt;b&gt;bold&lt;/b&gt;', $out);
        $this->assertStringContainsString('<p>', $out);
        $this->assertStringNotContainsString('<script', strtolower($out));

        \Illuminate\Database\Capsule\Manager::table('settings')->where('key', 'test_block_setting')->delete();
    }

    public function test_render_string_block_does_not_execute_blade_directives()
    {
        $out = render_string_block('@php echo "PWNED"; @endphp <p>ok</p>{{ setting(\'nonexistent_key_xyz\') }}');
        $this->assertStringNotContainsString('PWNED', $out);
        $this->assertStringNotContainsString('<?php', $out);
        $this->assertStringContainsString('ok', $out);
    }
}
