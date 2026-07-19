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
