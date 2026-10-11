<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Theme;

class ThemeAssetsTest extends TestCase
{
    private string $themeSlug = 'testtheme';
    private string $themeDir;
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2);
        $this->themeDir = $root . '/themes/' . $this->themeSlug;
        $this->publicDir = $root . '/public/themes/' . $this->themeSlug;

        // Fixture theme with assets
        @mkdir($this->themeDir . '/assets/css', 0775, true);
        @mkdir($this->themeDir . '/assets/js', 0775, true);
        file_put_contents($this->themeDir . '/assets/css/site.css', 'body { color: red; }');
        file_put_contents($this->themeDir . '/assets/js/app.js', 'console.log(1);');
        file_put_contents($this->themeDir . '/theme.json', json_encode(['name' => 'Test Theme']));
    }

    protected function tearDown(): void
    {
        foreach ([$this->publicDir, $this->themeDir] as $dir) {
            if (is_link($dir)) {
                @rmdir($dir);
                @unlink($dir);
            } elseif (is_dir($dir)) {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $file) {
                    $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
                }
                @rmdir($dir);
            }
        }
        parent::tearDown();
    }

    public function test_publish_assets_copies_theme_assets_into_public()
    {
        $this->assertTrue(Theme::publishAssets($this->themeSlug));

        $this->assertFileExists($this->publicDir . '/assets/css/site.css');
        $this->assertFileExists($this->publicDir . '/assets/js/app.js');
        $this->assertStringContainsString(
            'color: red',
            (string) file_get_contents($this->publicDir . '/assets/css/site.css')
        );
    }

    public function test_publish_assets_replaces_stale_content()
    {
        $this->assertTrue(Theme::publishAssets($this->themeSlug));
        $this->assertFileExists($this->publicDir . '/assets/css/site.css');

        // Remove the source file and re-publish: the copy must update.
        unlink($this->themeDir . '/assets/css/site.css');
        $this->assertTrue(Theme::publishAssets($this->themeSlug));
        $this->assertFileDoesNotExist($this->publicDir . '/assets/css/site.css');
        $this->assertFileExists($this->publicDir . '/assets/js/app.js');
    }

    public function test_publish_assets_returns_false_without_assets_dir()
    {
        @mkdir(dirname($this->themeDir) . '/emptytheme', 0775, true);
        try {
            $this->assertFalse(Theme::publishAssets('emptytheme'));
        } finally {
            @rmdir(dirname($this->themeDir) . '/emptytheme');
        }
    }
}
