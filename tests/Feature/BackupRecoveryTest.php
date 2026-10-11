<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\BackupManager;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Failure-path tests for restoreFromZipWithRecovery().
 *
 * Every MySQL test creates and drops its own disposable database on the
 * configured MariaDB/MySQL server (pankhcms_recovery_test_*). Tests are
 * skipped automatically when no server is reachable.
 */
class BackupRecoveryTest extends TestCase
{
    /** @var array<string> Zips that existed in storage/backups before this test. */
    private array $preExistingZips = [];

    /** @var array<string> Instruction files that existed before this test. */
    private array $preExistingInstructions = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        Capsule::table('settings')->updateOrInsert(['key' => 'maintenance_mode'], ['value' => '0']);

        // Remember every zip currently in storage/backups so tearDown only
        // removes archives created by this test — pre-existing backups and
        // unrelated files are never touched.
        $dir = dirname(__DIR__, 2) . '/storage/backups/';
        $this->preExistingZips = array_map('basename', glob($dir . '*.zip') ?: []);
        $this->preExistingInstructions = array_map('basename', glob($dir . 'RESTORE-FAILED-*.txt') ?: []);
    }

    protected function tearDown(): void
    {
        // Re-glob: recovery archives are written during the test, after
        // setUp snapshotted the directory. Only new files are removed.
        $dir = dirname(__DIR__, 2) . '/storage/backups/';
        foreach (glob($dir . '*.zip') ?: [] as $zip) {
            if (!in_array(basename($zip), $this->preExistingZips, true)) {
                @unlink($zip);
            }
        }
        foreach (glob($dir . 'RESTORE-FAILED-*.txt') ?: [] as $file) {
            if (!in_array(basename($file), $this->preExistingInstructions, true)) {
                @unlink($file);
            }
        }

        // Disposable MySQL databases are dropped even when a test fails.
        foreach ($this->disposableMysqlDbs as $name) {
            try {
                $this->mysqlRoot()->exec("DROP DATABASE IF EXISTS `$name`");
            } catch (\Throwable $e) {
                // best effort — server may be gone
            }
        }
        $this->disposableMysqlDbs = [];

        Capsule::table('settings')->where('key', 'maintenance_mode')->update(['value' => '0']);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    /* =====================================================
       Helpers
       ===================================================== */

    private function sqliteManager(string $dbFile): BackupManager
    {
        return new BackupManager(['driver' => 'sqlite', 'database' => $dbFile]);
    }

    private function makeSqliteDb(string $seedValue): string
    {
        $db = sys_get_temp_dir() . '/recovery_live_' . uniqid() . '.sqlite';
        file_put_contents($db, '');
        $pdo = new \PDO('sqlite:' . $db, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE probe (v TEXT)');
        $pdo->exec("INSERT INTO probe VALUES ('$seedValue')");
        $pdo = null;
        return $db;
    }

    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'recoveryzip') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return $path;
    }

    private function validBackupJson(): string
    {
        return json_encode(['cms' => 'PankhCMS', 'type' => 'database', 'database' => 'sqlite']);
    }



    /* =====================================================
       SQLite paths
       ===================================================== */

    public function test_successful_restore_keeps_recovery_archive()
    {
        $db = $this->makeSqliteDb('original');
        $manager = $this->sqliteManager($db);

        $good = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => file_get_contents($db), // snapshot of current state
        ]);

        // Wreck the live DB, then restore the good snapshot via the wrapper
        $pdo = new \PDO('sqlite:' . $db);
        $pdo->exec('DROP TABLE probe');
        $pdo->exec('CREATE TABLE probe (v TEXT)');
        $pdo->exec("INSERT INTO probe VALUES ('wrecked')");
        $pdo = null;

        $manager->restoreFromZipWithRecovery($good);

        $value = (new \PDO('sqlite:' . $db))->query('SELECT v FROM probe')->fetchColumn();
        $this->assertSame('original', $value);

        // The recovery archive is kept as a normal backup: a new zip must
        // exist beyond the ones present at setUp
        $dir = dirname(__DIR__, 2) . '/storage/backups/';
        $currentZips = array_map('basename', glob($dir . '*.zip') ?: []);
        $this->assertGreaterThan(count($this->preExistingZips), count($currentZips), 'recovery archive should be kept in storage/backups');
        @unlink($good);
    }

    public function test_failed_restore_recovers_to_pre_restore_state()
    {
        $db = $this->makeSqliteDb('original');
        $manager = $this->sqliteManager($db);

        // Snapshot the live (pre-restore) state so we can prove recovery
        $preState = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => file_get_contents($db),
        ]);

        // Primary archive: header-valid but corrupt — fails after validation
        $primary = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => 'SQLite format 3' . str_repeat('A', 4096),
        ]);

        try {
            $manager->restoreFromZipWithRecovery($primary);
            $this->fail('Expected the restore failure to propagate after recovery');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('recovered to its pre-restore state', $e->getMessage());
        }

        // Live DB must be exactly the pre-restore state (readable, original data)
        $value = (new \PDO('sqlite:' . $db))->query('SELECT v FROM probe')->fetchColumn();
        $this->assertSame('original', $value, 'recovery must return the database to its pre-restore state');
        @unlink($primary);
        @unlink($preState);
    }

    public function test_recovery_backup_creation_failure_aborts_without_touching_database()
    {
        $db = $this->makeSqliteDb('precious');
        $manager = $this->sqliteManager($db);

        // Make the live database unreadable so the recovery snapshot fails
        $primary = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => file_get_contents($db),
        ]);
        $brokenManager = new BrokenBackupManager(['driver' => 'sqlite', 'database' => $db]);

        try {
            $brokenManager->restoreFromZipWithRecovery($primary);
            $this->fail('Expected abort when recovery backup cannot be created');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Restore aborted', $e->getMessage());
            $this->assertStringContainsString('was not modified', $e->getMessage());
        }

        $value = (new \PDO('sqlite:' . $db))->query('SELECT v FROM probe')->fetchColumn();
        $this->assertSame('precious', $value, 'abort path must leave the live database untouched');
        @unlink($primary);
    }

    public function test_invalid_recovery_archive_aborts_without_touching_database()
    {
        $db = $this->makeSqliteDb('precious');
        $primary = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => file_get_contents($db),
        ]);

        // Manager whose recovery backup is corrupt
        $manager = new class(['driver' => 'sqlite', 'database' => $db]) extends BackupManager {
            public function createDatabaseBackup(string $notes = ''): string
            {
                $path = tempnam(sys_get_temp_dir(), 'badrecovery') . '.zip';
                $zip = new \ZipArchive();
                $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                $zip->addFromString('backup.json', json_encode(['cms' => 'NotPankhCMS']));
                $zip->close();
                return $path;
            }
        };

        try {
            $manager->restoreFromZipWithRecovery($primary);
            $this->fail('Expected abort when the recovery archive fails validation');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('failed validation', $e->getMessage());
        }

        $value = (new \PDO('sqlite:' . $db))->query('SELECT v FROM probe')->fetchColumn();
        $this->assertSame('precious', $value, 'abort path must leave the live database untouched');
        @unlink($primary);
    }

    public function test_double_failure_forces_maintenance_and_writes_instructions()
    {
        $db = $this->makeSqliteDb('whatever');
        $primary = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => 'SQLite format 3' . str_repeat('A', 4096),
        ]);

        $manager = new class(['driver' => 'sqlite', 'database' => $db]) extends BackupManager {
            public int $restoreCalls = 0;
            public function restoreFromZip(string $zipPath): void
            {
                $this->restoreCalls++;
                throw new \RuntimeException('simulated failure #' . $this->restoreCalls);
            }
        };

        try {
            $manager->restoreFromZipWithRecovery($primary);
            $this->fail('Expected the double-failure exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('automatic recovery failed', $e->getMessage());
            $this->assertStringContainsString('maintenance mode', $e->getMessage());
            $this->assertStringContainsString('Recovery backup:', $e->getMessage());
        }

        // Maintenance must be forced ON after a double failure
        $maint = Capsule::table('settings')->where('key', 'maintenance_mode')->value('value');
        $this->assertSame('1', (string) $maint, 'double failure must force maintenance mode');

        // Operator instructions must exist
        $instructions = glob(dirname(__DIR__, 2) . '/storage/backups/RESTORE-FAILED-*.txt') ?: [];
        $this->assertNotEmpty($instructions, 'an operator instructions file must be written');
        $contents = (string) file_get_contents($instructions[0]);
        $this->assertStringContainsString('operator action required', $contents);
        $this->assertStringContainsString('maintenance_mode', $contents);
        $this->assertStringContainsString('Recovery archive', $contents);
        @unlink($primary);
    }

    public function test_sequential_restores_are_independent()
    {
        $db = $this->makeSqliteDb('v1');
        $manager = $this->sqliteManager($db);

        $zip1 = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => file_get_contents($db),
        ]);

        $pdo = new \PDO('sqlite:' . $db);
        $pdo->exec("UPDATE probe SET v = 'v2'");
        $pdo = null;

        $zip2 = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => file_get_contents($db),
        ]);

        // Two sequential restores must both succeed and land on their own state
        $manager->restoreFromZipWithRecovery($zip2);
        $this->assertSame('v2', (new \PDO('sqlite:' . $db))->query('SELECT v FROM probe')->fetchColumn());

        $manager->restoreFromZipWithRecovery($zip1);
        $this->assertSame('v1', (new \PDO('sqlite:' . $db))->query('SELECT v FROM probe')->fetchColumn());
        @unlink($zip1);
        @unlink($zip2);
    }

    /* =====================================================
       MySQL paths (disposable databases on the configured server)
       ===================================================== */

    private function mysqlAvailable(): bool
    {
        try {
            new \PDO(
                'mysql:host=' . (getenv('DB_TEST_HOST') ?: '127.0.0.1')
                . ';port=' . (getenv('DB_TEST_PORT') ?: '3309'),
                getenv('DB_TEST_USER') ?: 'root',
                getenv('DB_TEST_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 3]
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function makeMysqlManager(string $database): BackupManager
    {
        return new BackupManager([
            'driver' => 'mysql',
            'host' => getenv('DB_TEST_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_TEST_PORT') ?: '3309',
            'database' => $database,
            'username' => getenv('DB_TEST_USER') ?: 'root',
            'password' => getenv('DB_TEST_PASS') ?: '',
        ]);
    }

    private function mysqlRoot(): \PDO
    {
        return new \PDO(
            'mysql:host=' . (getenv('DB_TEST_HOST') ?: '127.0.0.1')
            . ';port=' . (getenv('DB_TEST_PORT') ?: '3309'),
            getenv('DB_TEST_USER') ?: 'root',
            getenv('DB_TEST_PASS') ?: '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    private function createDisposableMysqlDb(): string
    {
        $name = 'pankhcms_recovery_test_' . uniqid();
        $this->mysqlRoot()->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        $this->disposableMysqlDbs[] = $name;
        return $name;
    }

    /** @var array<string> */
    private array $disposableMysqlDbs = [];

    public function test_mysql_failed_import_recovers_to_pre_restore_state()
    {
        if (!$this->mysqlAvailable()) {
            $this->markTestSkipped('No MySQL/MariaDB server available (set DB_TEST_HOST/DB_TEST_PORT to enable).');
        }
        $database = $this->createDisposableMysqlDb();

        // Live state: one table, one row
        $live = $this->makeMysqlManager($database);
        $pdo = new \PDO(
            "mysql:host=" . (getenv('DB_TEST_HOST') ?: '127.0.0.1') . ";port=" . (getenv('DB_TEST_PORT') ?: '3309') . ";dbname=$database",
            getenv('DB_TEST_USER') ?: 'root', getenv('DB_TEST_PASS') ?: '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec('CREATE TABLE probe (v VARCHAR(255))');
        $pdo->exec("INSERT INTO probe VALUES ('original')");
        $pdo = null;

        // Primary archive: valid schema statements, then invalid SQL — the
        // import fails halfway (partial state on a naive restore).
        $primary = $this->makeZip([
            'backup.json' => json_encode(['cms' => 'PankhCMS', 'type' => 'database', 'database' => 'mysql']),
            'database.sql' => "SET FOREIGN_KEY_CHECKS=0;\n"
                . "DROP TABLE IF EXISTS `probe`;\n"
                . "CREATE TABLE `probe` (`v` VARCHAR(255));\n"
                . "INSERT INTO `probe` VALUES ('halfway');\n"
                . "THIS IS NOT VALID SQL AT ALL;\n"
                . "SET FOREIGN_KEY_CHECKS=1;\n",
        ]);

        try {
            $this->makeMysqlManager($database)->restoreFromZipWithRecovery($primary);
            $this->fail('Expected the restore failure to propagate after recovery');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('recovered to its pre-restore state', $e->getMessage());
        }

        // The pre-restore state must be back: original table and row
        $pdo = new \PDO(
            "mysql:host=" . (getenv('DB_TEST_HOST') ?: '127.0.0.1') . ";port=" . (getenv('DB_TEST_PORT') ?: '3309') . ";dbname=$database",
            getenv('DB_TEST_USER') ?: 'root', getenv('DB_TEST_PASS') ?: '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $values = $pdo->query('SELECT v FROM probe')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains('original', $values, 'recovery must restore the pre-restore data');
        $this->assertNotContains('halfway', $values, 'the partially imported row must be gone');

        // Global FK checks must be untouched by the failed import
        $globalFk = $pdo->query('SELECT @@GLOBAL.foreign_key_checks')->fetchColumn();
        $this->assertSame(1, (int) $globalFk, 'failed imports must not disable global FK checks');

        $pdo = null;
        @unlink($primary);
    }

    public function test_mysql_connection_failure_aborts_without_damage()
    {
        if (!$this->mysqlAvailable()) {
            $this->markTestSkipped('No MySQL/MariaDB server available.');
        }

        // A manager pointed at a database that does not exist / unreachable port
        $manager = new BackupManager([
            'driver' => 'mysql',
            'host' => getenv('DB_TEST_HOST') ?: '127.0.0.1',
            'port' => '13306', // nothing listens here
            'database' => 'no_such_db',
            'username' => 'root',
            'password' => '',
        ]);

        $primary = $this->makeZip([
            'backup.json' => json_encode(['cms' => 'PankhCMS', 'type' => 'database', 'database' => 'mysql']),
            'database.sql' => 'SELECT 1;',
        ]);

        try {
            $manager->restoreFromZipWithRecovery($primary);
            $this->fail('Expected abort when the database server is unreachable');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Restore aborted', $e->getMessage());
            $this->assertStringContainsString('was not modified', $e->getMessage());
        }
        @unlink($primary);
    }

    public function test_mysql_insufficient_privileges_abort()
    {
        if (!$this->mysqlAvailable()) {
            $this->markTestSkipped('No MySQL/MariaDB server available.');
        }
        $database = $this->createDisposableMysqlDb();

        // A user that may connect but may not create databases or tables
        $root = $this->mysqlRoot();
        $root->exec("CREATE USER IF NOT EXISTS 'pankhcms_limited'@'localhost' IDENTIFIED BY 'limited-pass'");
        $root->exec("GRANT USAGE ON *.* TO 'pankhcms_limited'@'localhost'");
        $root->exec("GRANT SELECT ON `$database`.* TO 'pankhcms_limited'@'localhost'");

        try {
            $manager = new BackupManager([
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => getenv('DB_TEST_PORT') ?: '3309',
                'database' => $database,
                'username' => 'pankhcms_limited',
                'password' => 'limited-pass',
            ]);

            $primary = $this->makeZip([
                'backup.json' => json_encode(['cms' => 'PankhCMS', 'type' => 'database', 'database' => 'mysql']),
                'database.sql' => 'CREATE TABLE `probe` (`v` VARCHAR(255));',
            ]);

            try {
                $manager->restoreFromZipWithRecovery($primary);
                $this->fail('Expected abort under insufficient privileges');
            } catch (\RuntimeException $e) {
                // The recovery backup itself needs SHOW TABLES/SELECT only —
                // the abort must reference either recovery creation or the restore.
                $this->assertTrue(
                    str_contains($e->getMessage(), 'Restore aborted')
                    || str_contains($e->getMessage(), 'recovered to its pre-restore state'),
                    'unexpected failure mode: ' . $e->getMessage()
                );
            }

            // The limited user cannot have created anything
            $tables = $root->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$database'")->fetchColumn();
            $this->assertSame(0, (int) $tables, 'no tables may be created under insufficient privileges');
        } finally {
            $root->exec("DROP USER IF EXISTS 'pankhcms_limited'@'localhost'");
                @unlink($primary ?? null);
        }
    }
}

/**
 * Test double whose recovery-backup creation always fails.
 */
class BrokenBackupManager extends BackupManager
{
    public function createDatabaseBackup(string $notes = ''): string
    {
        throw new \RuntimeException('simulated backup failure');
    }
}
