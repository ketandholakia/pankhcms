<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use PluginManager;
use Illuminate\Database\Capsule\Manager as DB;

class PluginManagerTest extends TestCase
{
    protected string $pluginDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pluginDir = dirname(__DIR__, 2) . '/plugins/TestPlugin';
        
        if (!is_dir($this->pluginDir)) {
            mkdir($this->pluginDir, 0777, true);
        }

        // Create a dummy plugin.json
        file_put_contents($this->pluginDir . '/plugin.json', json_encode([
            'name' => 'Test',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'main' => 'Plugin.php'
        ]));

        // Create a dummy Plugin.php
        $php = <<<PHP
<?php
class TestPlugin {
    public \$registered = false;
    public \$booted = false;
    public \$activated = false;
    public \$deactivated = false;
    
    public function __construct(\$meta, \$dir) {}
    public function register() { \$this->registered = true; }
    public function boot() { \$this->booted = true; }
    public function activate() { \$this->activated = true; }
    public function deactivate() { \$this->deactivated = true; }
}
PHP;
        file_put_contents($this->pluginDir . '/Plugin.php', $php);
        
        // Ensure the plugins table exists (create-tables script should have created it)
        DB::table('plugins')->where('slug', 'test-plugin')->delete();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->pluginDir)) {
            @unlink($this->pluginDir . '/Plugin.php');
            @unlink($this->pluginDir . '/plugin.json');
            @rmdir($this->pluginDir);
        }
        DB::table('plugins')->where('slug', 'test-plugin')->delete();
        parent::tearDown();
    }

    public function test_discover_plugins()
    {
        $plugins = PluginManager::discoverAll();
        $this->assertArrayHasKey('test-plugin', $plugins);
        $this->assertEquals('Test', $plugins['test-plugin']['name']);
    }

    public function test_uninstall_rejects_path_traversal_and_deletes_nothing()
    {
        // A directory outside plugins/ that a traversal attempt would target
        $outsideDir = dirname($this->pluginDir, 2) . '/storage/outside-target';
        if (!is_dir($outsideDir)) {
            mkdir($outsideDir, 0777, true);
        }
        file_put_contents($outsideDir . '/keep.txt', 'must survive');

        $this->assertFalse(PluginManager::uninstall('../storage/outside-target'));
        $this->assertFalse(PluginManager::uninstall('..%2F..%2Fetc'));
        $this->assertFalse(PluginManager::uninstall('TestPlugin')); // valid dir name, not a discovered slug
        $this->assertFalse(PluginManager::uninstall('nonexistent-plugin'));

        $this->assertFileExists($outsideDir . '/keep.txt');
        $this->assertTrue(is_dir($outsideDir));

        // The real test plugin must be untouched too
        $this->assertFileExists($this->pluginDir . '/plugin.json');

        @unlink($outsideDir . '/keep.txt');
        @rmdir($outsideDir);
    }

    public function test_uninstall_removes_discovered_plugin()
    {
        $this->assertTrue(PluginManager::activate('test-plugin'));
        $this->assertTrue(PluginManager::uninstall('test-plugin'));
        $this->assertFileDoesNotExist($this->pluginDir . '/plugin.json');
        $this->assertFileDoesNotExist($this->pluginDir . '/Plugin.php');
        $this->assertFalse(is_dir($this->pluginDir));
    }

    public function test_slug_validation()
    {
        $this->assertTrue(PluginManager::isValidSlug('test-plugin'));
        $this->assertTrue(PluginManager::isValidSlug('a_1'));
        $this->assertFalse(PluginManager::isValidSlug('../evil'));
        $this->assertFalse(PluginManager::isValidSlug('Evil'));
        $this->assertFalse(PluginManager::isValidSlug(''));
        $this->assertFalse(PluginManager::isValidSlug('a/b'));
    }

    public function test_activate_and_deactivate_plugin()
    {
        $this->assertTrue(PluginManager::activate('test-plugin'));
        
        $active = PluginManager::getActivePlugins();
        $this->assertContains('test-plugin', $active);
        
        // Test boot sequence
        PluginManager::boot();
        $instances = PluginManager::instances();
        $this->assertArrayHasKey('test-plugin', $instances);
        $this->assertTrue($instances['test-plugin']->registered);
        $this->assertTrue($instances['test-plugin']->booted);
        
        $this->assertTrue(PluginManager::deactivate('test-plugin'));
        $activeAfterDeactivate = PluginManager::getActivePlugins();
        $this->assertNotContains('test-plugin', $activeAfterDeactivate);
    }
}
