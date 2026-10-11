<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\BackupManager;
use Illuminate\Database\Capsule\Manager as Capsule;
use ZipArchive;

class BackupManagerTest extends TestCase
{
    private string $dbFile;
    private array $createdZips = [];

    protected function setUp(): void
    {
        parent::setUp();

        // A file-backed SQLite database (VACUUM INTO needs a real file)
        $this->dbFile = sys_get_temp_dir() . '/pankhcms_backup_test_' . uniqid() . '.sqlite';

        $pdo = new \PDO('sqlite:' . $this->dbFile, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('CREATE TABLE backup_demo (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO backup_demo (name) VALUES ('alpha'), ('beta')");
        $pdo = null;

        // Keep the storage/backups dir clean of test artifacts
        $backups = glob(dirname(__DIR__, 2) . '/storage/backups/*.zip') ?: [];
        foreach ($backups as $old) {
            @unlink($old);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdZips as $zip) {
            @unlink($zip);
        }
        $this->createdZips = [];
        @unlink($this->dbFile);
        parent::tearDown();
    }

    private function manager(): BackupManager
    {
        return new BackupManager([
            'driver' => 'sqlite',
            'database' => $this->dbFile,
        ]);
    }

    public function test_database_backup_zip_contains_sqlite_snapshot()
    {
        $zipPath = $this->manager()->createDatabaseBackup('test dump');
        $this->createdZips[] = $zipPath;
        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $snapshot = $zip->getFromName('database.sqlite');
        $meta = $zip->getFromName('backup.json');
        $zip->close();

        // The snapshot must be a verbatim SQLite file (magic header at 0)
        $this->assertNotFalse($snapshot);
        $this->assertSame('SQLite format 3', substr((string) $snapshot, 0, 15));

        $metaDecoded = json_decode((string) $meta, true);
        $this->assertSame('database', $metaDecoded['type'] ?? null);
        $this->assertSame('test dump', $metaDecoded['notes'] ?? null);
    }

    public function test_sqlite_backup_uses_vacuum_into_snapshot()
    {
        $zipPath = $this->manager()->createDatabaseBackup();
        $this->createdZips[] = $zipPath;

        // Modify the live DB after the backup: the snapshot must not contain it
        $pdo = new \PDO('sqlite:' . $this->dbFile);
        $pdo->exec("INSERT INTO backup_demo (name) VALUES ('added-after-backup')");
        $pdo = null;

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $snapshot = (string) $zip->getFromName('database.sqlite');
        $zip->close();

        // Open the snapshot itself and query it — proves it is valid SQLite
        $snapFile = sys_get_temp_dir() . '/snap_check_' . uniqid() . '.sqlite';
        file_put_contents($snapFile, $snapshot);
        $pdo = new \PDO('sqlite:' . $snapFile);
        $names = $pdo->query('SELECT name FROM backup_demo')->fetchAll(\PDO::FETCH_COLUMN);
        $pdo = null;
        unlink($snapFile);

        $this->assertContains('alpha', $names);
        $this->assertContains('beta', $names);
        $this->assertNotContains('added-after-backup', $names);
    }

    public function test_full_backup_excludes_storage_directories()
    {
        $zipPath = $this->manager()->createFullBackup('full test');
        $this->createdZips[] = $zipPath;

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains('database.sqlite', $names);
        $this->assertContains('backup.json', $names);

        foreach ($names as $name) {
            $this->assertStringNotContainsString('storage/', $name);
            $this->assertStringNotContainsString('storage/logs', $name);
            $this->assertStringNotContainsString('storage/cache', $name);
            $this->assertStringNotContainsString('storage/backups', $name);
        }
    }

    public function test_database_backup_records_meta()
    {
        // smoke test that two backups produce distinct filenames
        $a = $this->manager()->createDatabaseBackup('one');
        $b = $this->manager()->createDatabaseBackup('two');
        $this->createdZips[] = $a;
        $this->createdZips[] = $b;

        $this->assertNotSame($a, $b);
        $this->assertFileExists($a);
        $this->assertFileExists($b);
    }

    /* =========================================================
       RESTORE
       ========================================================= */

    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bakzip') . '.zip';
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

    public function test_restore_round_trips_sqlite_data()
    {
        $zipPath = $this->manager()->createDatabaseBackup('round trip');
        $this->createdZips[] = $zipPath;

        // Wreck the live database after the backup
        $pdo = new \PDO('sqlite:' . $this->dbFile);
        $pdo->exec('DROP TABLE backup_demo');
        $pdo->exec('CREATE TABLE backup_demo (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO backup_demo (name) VALUES ('wrecked')");
        $pdo = null;

        $this->manager()->restoreFromZip($zipPath);

        $pdo = new \PDO('sqlite:' . $this->dbFile);
        $names = $pdo->query('SELECT name FROM backup_demo')->fetchAll(\PDO::FETCH_COLUMN);
        $pdo = null;

        $this->assertContains('alpha', $names);
        $this->assertContains('beta', $names);
        $this->assertNotContains('wrecked', $names);
    }

    public function test_restore_rejects_missing_manifest()
    {
        $zip = $this->makeZip(['database.sqlite' => 'SQLite format 3 whatever']);

        try {
            $this->manager()->restoreFromZip($zip);
            $this->fail('Expected RuntimeException for missing backup.json');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('backup.json', $e->getMessage());
        }
        @unlink($zip);
    }

    public function test_restore_rejects_non_pankhcms_archive()
    {
        $zip = $this->makeZip([
            'backup.json' => json_encode(['cms' => 'SomethingElse']),
            'database.sqlite' => 'SQLite format 3',
        ]);

        try {
            $this->manager()->restoreFromZip($zip);
            $this->fail('Expected RuntimeException for foreign archive');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('PankhCMS', $e->getMessage());
        }
        @unlink($zip);
    }

    public function test_restore_rejects_traversal_entries()
    {
        $zip = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => 'SQLite format 3',
            '../evil.txt' => 'pwned',
        ]);

        try {
            $this->manager()->restoreFromZip($zip);
            $this->fail('Expected RuntimeException for traversal entry');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('traversal', $e->getMessage());
        }
        $this->assertFileDoesNotExist(dirname($this->dbFile) . '/evil.txt');
        @unlink($zip);
    }

    public function test_restore_rejects_missing_database_file()
    {
        $zip = $this->makeZip(['backup.json' => $this->validBackupJson()]);

        try {
            $this->manager()->restoreFromZip($zip);
            $this->fail('Expected RuntimeException for missing database entry');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('database.sqlite', $e->getMessage());
        }
        @unlink($zip);
    }

    public function test_restore_rejects_corrupt_snapshot()
    {
        $zip = $this->makeZip([
            'backup.json' => $this->validBackupJson(),
            'database.sqlite' => '-- not really a database',
        ]);

        try {
            $this->manager()->restoreFromZip($zip);
            $this->fail('Expected RuntimeException for corrupt snapshot');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not a valid SQLite', $e->getMessage());
        }
        @unlink($zip);
    }

    public function test_restore_toggles_maintenance_mode_off_afterwards()
    {
        $zipPath = $this->manager()->createDatabaseBackup('maintenance check');
        $this->createdZips[] = $zipPath;

        $this->manager()->restoreFromZip($zipPath);

        $value = \Illuminate\Database\Capsule\Manager::table('settings')
            ->where('key', 'maintenance_mode')->value('value');
        $this->assertSame('0', (string) $value, 'maintenance mode must be switched off after restore');
    }
}
