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

    public function test_database_backup_zip_contains_sql_dump()
    {
        $zipPath = $this->manager()->createDatabaseBackup('test dump');
        $this->createdZips[] = $zipPath;
        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $sql = $zip->getFromName('database.sql');
        $meta = $zip->getFromName('backup.json');
        $zip->close();

        $this->assertNotFalse($sql);
        $this->assertStringContainsString('backup_demo', $sql);

        $metaDecoded = json_decode((string) $meta, true);
        $this->assertSame('database', $metaDecoded['type'] ?? null);
        $this->assertSame('test dump', $metaDecoded['notes'] ?? null);
    }

    public function test_sqlite_backup_uses_vacuum_into_snapshot()
    {
        $zipPath = $this->manager()->createDatabaseBackup();
        $this->createdZips[] = $zipPath;

        // Modify the live DB after the backup: the dump must not contain it
        $pdo = new \PDO('sqlite:' . $this->dbFile);
        $pdo->exec("INSERT INTO backup_demo (name) VALUES ('added-after-backup')");
        $pdo = null;

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $sql = (string) $zip->getFromName('database.sql');
        $zip->close();

        $this->assertStringNotContainsString('added-after-backup', $sql);
        $this->assertStringContainsString('alpha', $sql);
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

        $this->assertContains('database.sql', $names);
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
}
