<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Theme;

/**
 * Theme slugs must be ^[a-z0-9_-]+$ and resolve to a directory directly
 * under themes/ — '../app', '..' and 'a/b' must never activate (or be
 * validated as) a theme, whether they arrive from the admin form or
 * from the ACTIVE_THEME / DEFAULT_THEME environment values.
 */
class ThemeSlugValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['ACTIVE_THEME'], $_ENV['DEFAULT_THEME']);
        putenv('ACTIVE_THEME');
        putenv('DEFAULT_THEME');
        parent::tearDown();
    }

    public function test_normalize_rejects_traversal_and_nested_paths()
    {
        $this->assertNull(Theme::normalizeThemeSlug('../app'), "'../app' must be rejected");
        $this->assertNull(Theme::normalizeThemeSlug('..'), "'..' must be rejected");
        $this->assertNull(Theme::normalizeThemeSlug('a/b'), "'a/b' must be rejected");
        $this->assertNull(Theme::normalizeThemeSlug('..\\app'), "backslash traversal must be rejected");
        $this->assertNull(Theme::normalizeThemeSlug(' UPPER '), "uppercase/whitespace must be rejected");
        $this->assertNull(Theme::normalizeThemeSlug('theme!name'), "special characters must be rejected");
        $this->assertNull(Theme::normalizeThemeSlug(''));
        $this->assertNull(Theme::normalizeThemeSlug('does-not-exist'), "unknown themes must be rejected");
    }

    public function test_normalize_accepts_the_real_theme()
    {
        $this->assertSame('pankhcmsstarter', Theme::normalizeThemeSlug('pankhcmsstarter'));
        $this->assertSame('pankhcmsstarter', Theme::normalizeThemeSlug(' pankhcmsstarter '));
    }

    public function test_theme_controller_theme_exists_applies_the_same_rule()
    {
        $method = new \ReflectionMethod(\App\Controllers\Admin\ThemeController::class, 'themeExists');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null, '../app'));
        $this->assertFalse($method->invoke(null, '..'));
        $this->assertFalse($method->invoke(null, 'a/b'));
        $this->assertTrue($method->invoke(null, 'pankhcmsstarter'));
    }

    public function test_active_theme_env_traversal_falls_back_to_a_real_theme()
    {
        $_ENV['ACTIVE_THEME'] = '../app';
        $_ENV['DEFAULT_THEME'] = '..';

        $active = Theme::active();
        $this->assertNotSame('../app', $active);
        $this->assertNotSame('..', $active);
        $this->assertSame(
            Theme::normalizeThemeSlug($active),
            $active,
            'whatever activates must itself pass the strict validator'
        );
    }
}
