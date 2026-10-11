<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use PluginManager;

class PluginZipTest extends TestCase
{
    private array $installedSlugs = [];

    protected function tearDown(): void
    {
        foreach ($this->installedSlugs as $slug) {
            $dir = PluginManager::getPluginDir() . '/' . $slug;
            if (is_dir($dir)) {
                @unlink($dir . '/plugin.json');
                @unlink($dir . '/Plugin.php');
                @rmdir($dir);
            }
        }
        $this->installedSlugs = [];
        parent::tearDown();
    }

    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'plugzip') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return $path;
    }

    private function validManifest(string $slug): string
    {
        return json_encode([
            'name' => 'Zipped',
            'slug' => $slug,
            'version' => '1.0.0',
            'main' => 'Plugin.php',
        ]);
    }

    public function test_valid_zip_is_installed_into_new_slug_directory()
    {
        $zip = $this->makeZip([
            'plugin.json' => $this->validManifest('zipped-plugin'),
            'Plugin.php' => '<?php class ZippedPlugin {}',
        ]);

        $slug = PluginManager::installFromZip($zip);
        $this->assertSame('zipped-plugin', $slug);
        $this->installedSlugs[] = $slug;

        $target = PluginManager::getPluginDir() . '/zipped-plugin';
        $this->assertFileExists($target . '/plugin.json');
        $this->assertFileExists($target . '/Plugin.php');

        @unlink($zip);
    }

    public function test_traversal_entry_is_rejected_and_nothing_extracted()
    {
        $zip = $this->makeZip([
            'plugin.json' => $this->validManifest('evil-plugin'),
            '../evil.txt' => 'pwned',
        ]);

        try {
            PluginManager::installFromZip($zip);
            $this->fail('Expected RuntimeException for traversal entry');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('traversal', $e->getMessage());
        }

        $this->assertFileDoesNotExist(PluginManager::getPluginDir() . '/evil-plugin');
        $this->assertFileDoesNotExist(dirname(PluginManager::getPluginDir()) . '/evil.txt');
        @unlink($zip);
    }

    public function test_absolute_path_entry_is_rejected()
    {
        $zip = $this->makeZip([
            'plugin.json' => $this->validManifest('evil-plugin'),
            '/tmp/evil.txt' => 'pwned',
        ]);

        $this->expectException(\RuntimeException::class);
        try {
            PluginManager::installFromZip($zip);
        } finally {
            $this->assertFileDoesNotExist(PluginManager::getPluginDir() . '/evil-plugin');
            @unlink($zip);
        }
    }

    public function test_zip_without_manifest_is_rejected()
    {
        $zip = $this->makeZip(['Plugin.php' => '<?php class NoManifestPlugin {}']);

        try {
            PluginManager::installFromZip($zip);
            $this->fail('Expected RuntimeException for missing plugin.json');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('plugin.json', $e->getMessage());
        }
        @unlink($zip);
    }

    public function test_invalid_manifest_slug_is_rejected()
    {
        $zip = $this->makeZip([
            'plugin.json' => $this->validManifest('../Bad Slug!'),
            'Plugin.php' => '<?php class BadPlugin {}',
        ]);

        $this->expectException(\RuntimeException::class);
        try {
            PluginManager::installFromZip($zip);
        } finally {
            @unlink($zip);
        }
    }

    public function test_existing_slug_directory_is_rejected()
    {
        $zip = $this->makeZip([
            'plugin.json' => $this->validManifest('zipped-plugin'),
            'Plugin.php' => '<?php class ZippedPlugin {}',
        ]);

        // First install succeeds
        $slug = PluginManager::installFromZip($zip);
        $this->installedSlugs[] = $slug;
        @unlink($zip);

        // A second archive with the same slug must be refused
        $zip2 = $this->makeZip([
            'plugin.json' => $this->validManifest('zipped-plugin'),
            'Plugin.php' => '<?php class ZippedPlugin {}',
        ]);

        $this->expectException(\RuntimeException::class);
        try {
            PluginManager::installFromZip($zip2);
        } finally {
            @unlink($zip2);
        }
    }
}
