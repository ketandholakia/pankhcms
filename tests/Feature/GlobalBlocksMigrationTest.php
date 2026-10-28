<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration 0012: brings pre-fix global_blocks tables (missing type /
 * status columns) up to the shape GlobalBlockController writes and
 * blocks()/blocks_html() read — WITHOUT touching existing block records.
 *
 * The two scenarios are simulated against real SQLite databases:
 * - "existing upgrade": create the OLD global_blocks shape, run the
 *   migration file, verify columns exist and records survive.
 * - "fresh install": 0001 (now fixed) creates the columns; running the
 *   migration file afterwards must be a no-op.
 */
class GlobalBlocksMigrationTest extends TestCase
{
    private string $dbFile;
    private array $dbConfigBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbFile = sys_get_temp_dir() . '/gb_mig_' . uniqid() . '.sqlite';
        file_put_contents($this->dbFile, '');

        // The migration file operates on the global Capsule default; point
        // it at this test's disposable database.
        $this->dbConfigBackup = [
            'connection' => $_ENV['DB_CONNECTION'] ?? null,
            'database' => $_ENV['DB_DATABASE'] ?? null,
        ];
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->dbFile;
        putenv('DB_CONNECTION=sqlite');

        Capsule::connection()->setPdo(new \PDO('sqlite:' . $this->dbFile));
        Capsule::connection()->setReadPdo(new \PDO('sqlite:' . $this->dbFile));
    }

    protected function tearDown(): void
    {
        if (isset($this->dbConfigBackup['database'])) {
            $_ENV['DB_DATABASE'] = $this->dbConfigBackup['database'];
            $_ENV['DB_CONNECTION'] = $this->dbConfigBackup['connection'];
            putenv('DB_CONNECTION=' . $this->dbConfigBackup['connection']);
        }
        if (is_file($this->dbFile)) {
            @unlink($this->dbFile);
        }
        parent::tearDown();
    }

    private function runMigration(): void
    {
        $cx = require dirname(__DIR__, 2) . '/database/migrations/0012_align_global_blocks.php';
        if (is_string($cx)) {
            echo $cx; // migration early-returned a message
        }
    }

    private function createOldShapeTable(): void
    {
        Capsule::schema()->create('global_blocks', function ($t) {
            $t->increments('id');
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('title')->nullable();
            $t->boolean('show_title')->default(true);
            $t->text('content')->nullable();
            $t->timestamps();
        });
    }

    private function columns(): array
    {
        return array_column(Capsule::select("PRAGMA table_info(global_blocks)"), 'name');
    }

    public function test_existing_schema_gains_columns_and_keeps_records()
    {
        $this->createOldShapeTable();

        // Two production-shaped records inserted BEFORE the migration.
        Capsule::table('global_blocks')->insert([
            ['name' => 'welcome', 'slug' => 'welcome', 'title' => 'Welcome', 'show_title' => 1, 'content' => '<p>Hello</p>'],
            ['name' => 'promo', 'slug' => 'promo', 'title' => 'Promo', 'show_title' => 0, 'content' => '{"data":{"text":"Promo body"}}'],
        ]);

        $this->assertNotContains('type', $this->columns());
        $this->assertNotContains('status', $this->columns());

        $this->runMigration();

        $this->assertContains('type', $this->columns());
        $this->assertContains('status', $this->columns());

        // Both pre-existing records must survive, with the defaults applied.
        $rows = Capsule::table('global_blocks')->orderBy('name')->get();
        $this->assertCount(2, $rows, 'existing global block records must be preserved');
        $welcome = $rows->firstWhere('name', 'welcome');
        $promo = $rows->firstWhere('name', 'promo');
        $this->assertNotNull($welcome);
        $this->assertNotNull($promo);
        $this->assertSame('text', $welcome->type, 'new type column must carry its default');
        $this->assertSame(1, (int) $welcome->status, 'new status column must carry its default');
        $this->assertSame('<p>Hello</p>', (string) $welcome->content, 'existing content must be untouched');
        $this->assertSame(0, (int) $promo->show_title, 'unrelated columns must be untouched');
    }

    public function test_migration_is_rerunnable_and_skips_fresh_shape()
    {
        // Fresh install shape: 0001 (fixed) already creates both columns.
        Capsule::schema()->create('global_blocks', function ($t) {
            $t->increments('id');
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('title')->nullable();
            $t->string('type')->default('text');
            $t->boolean('status')->default(true);
            $t->boolean('show_title')->default(true);
            $t->text('content')->nullable();
            $t->timestamps();
        });

        // Must not throw when re-run against the fresh shape.
        $this->runMigration();
        $this->runMigration();

        $this->assertContains('type', $this->columns());
        $this->assertContains('status', $this->columns());
    }

    public function test_migration_noops_when_table_missing()
    {
        // No global_blocks table at all (pre-0001 install): the migration
        // must return cleanly instead of creating the table itself.
        $this->runMigration();
        $this->assertFalse(Capsule::schema()->hasTable('global_blocks'));
    }
}
