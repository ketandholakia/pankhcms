<?php

namespace App\Core;

use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Creates database and full-site backups as ZIP archives.
 *
 * Full backups contain the database dump, public/uploads and themes.
 * storage/backups, storage/cache and storage/logs are never included.
 */
class BackupManager
{
    private string $backupDir;
    private array $dbConfig;

    public function __construct(array $dbConfig)
    {
        $this->dbConfig = $dbConfig;
        $this->backupDir = dirname(__DIR__, 2) . '/storage/backups/';
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
    }

    /* =========================================================
       CREATE BACKUPS
       ========================================================= */

    public function createFullBackup(string $notes = ''): string
    {
        $root = dirname(__DIR__, 2);
        $tmpDir = sys_get_temp_dir() . '/pankhcms_backup_' . uniqid();

        if (!mkdir($tmpDir, 0775, true)) {
            throw new RuntimeException('Failed to create temporary backup directory.');
        }

        try {
            $this->backupDatabase($tmpDir);

            // Media and themes are part of a full backup; storage/backups,
            // storage/cache and storage/logs are deliberately excluded.
            $this->copyDirectory($root . '/public/uploads', $tmpDir . '/uploads');
            $this->copyDirectory($root . '/themes', $tmpDir . '/themes');

            file_put_contents(
                $tmpDir . '/backup.json',
                json_encode($this->metadata('full', $notes), JSON_PRETTY_PRINT)
            );

            return $this->packageBackup($tmpDir, 'full');
        } finally {
            $this->cleanup($tmpDir);
        }
    }

    public function createDatabaseBackup(string $notes = ''): string
    {
        $tmpDir = sys_get_temp_dir() . '/pankhcms_db_backup_' . uniqid();

        if (!mkdir($tmpDir, 0775, true)) {
            throw new RuntimeException('Failed to create temporary backup directory.');
        }

        try {
            $this->backupDatabase($tmpDir);

            file_put_contents(
                $tmpDir . '/backup.json',
                json_encode($this->metadata('database', $notes), JSON_PRETTY_PRINT)
            );

            return $this->packageBackup($tmpDir, 'database');
        } finally {
            $this->cleanup($tmpDir);
        }
    }

    /* =========================================================
       RESTORE (database only)
       ========================================================= */

    /**
     * Restore the database from a backup ZIP.
     *
     * Every ZIP entry name is validated (no absolute paths, no "..", no
     * backslashes), backup.json must identify a PankhCMS archive, and the
     * archive must contain the database file for the configured driver.
     * Maintenance mode is enabled for the duration of the restore.
     *
     * Only the database is restored; uploaded files and themes are left
     * untouched on purpose.
     */
    public function restoreFromZip(string $zipPath): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP zip extension is required for restore.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Cannot open backup ZIP.');
        }

        try {
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === '' || str_contains($name, '\\') || str_starts_with($name, '/')
                    || preg_match('#^[A-Za-z]:#', $name) === 1) {
                    throw new RuntimeException('Invalid entry name in backup ZIP.');
                }
                foreach (explode('/', $name) as $part) {
                    if ($part === '..') {
                        throw new RuntimeException('Path traversal in backup ZIP is not allowed.');
                    }
                }
                $names[] = $name;
            }

            if (!in_array('backup.json', $names, true)) {
                throw new RuntimeException('backup.json is missing from the backup.');
            }

            $meta = json_decode((string) $zip->getFromName('backup.json'), true);
            if (!is_array($meta) || ($meta['cms'] ?? '') !== 'PankhCMS') {
                throw new RuntimeException('This does not look like a PankhCMS backup.');
            }

            $driver = $this->dbConfig['driver'] ?? 'sqlite';
            $entry = $driver === 'sqlite' ? 'database.sqlite' : 'database.sql';
            if (!in_array($entry, $names, true)) {
                throw new RuntimeException("The backup does not contain a $entry for the current database driver.");
            }

            $this->setMaintenance(true);

            try {
                if ($driver === 'sqlite') {
                    $this->restoreSqlite((string) $zip->getFromName($entry));
                } elseif ($driver === 'mysql') {
                    $this->restoreMysql((string) $zip->getFromName($entry));
                } else {
                    throw new RuntimeException('Unsupported database driver: ' . $driver);
                }
            } finally {
                $this->setMaintenance(false);
            }
        } finally {
            $zip->close();
        }
    }

    private function restoreSqlite(string $snapshot): void
    {
        $path = $this->dbConfig['database'] ?? '';
        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('SQLite restore needs a file-backed database.');
        }

        // A valid snapshot must start with the SQLite magic header.
        if (substr($snapshot, 0, 15) !== 'SQLite format 3') {
            throw new RuntimeException('The database snapshot is not a valid SQLite file.');
        }

        $dbDir = dirname($path);
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0775, true);
        }

        // Release the open handle so the file can be replaced; the next
        // query reconnects automatically. Only if Capsule is actually
        // using this database (disconnecting an unrelated connection —
        // e.g. an in-memory test database — would wipe it).
        if (class_exists('Illuminate\Database\Capsule\Manager')) {
            try {
                $connection = \Illuminate\Database\Capsule\Manager::connection();
                if ($connection !== null && ($connection->getConfig('database') ?? '') === $path) {
                    $connection->disconnect();
                }
            } catch (\Throwable $e) {
                // No active connection is fine.
            }
        }

        $tmp = $path . '.restore-' . uniqid();
        if (file_put_contents($tmp, $snapshot) === false || !rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Failed to write the restored database file.');
        }
    }

    private function restoreMysql(string $dump): void
    {
        if (trim($dump) === '') {
            throw new RuntimeException('The backup contains an empty database dump.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->dbConfig['host'] ?? '127.0.0.1',
            $this->dbConfig['port'] ?? '3306',
            $this->dbConfig['database'] ?? ''
        );

        $pdo = new PDO($dsn, (string) ($this->dbConfig['username'] ?? ''), (string) ($this->dbConfig['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);

        // The dump was produced by backupMysql() with FOREIGN_KEY_CHECKS=0.
        $pdo->exec($dump);
        $pdo = null;
    }

    private function setMaintenance(bool $on): void
    {
        try {
            if (!class_exists('Illuminate\Database\Capsule\Manager')) {
                return;
            }
            $capsule = 'Illuminate\Database\Capsule\Manager';
            if (!$capsule::schema()->hasTable('settings')) {
                return;
            }
            $capsule::table('settings')->updateOrInsert(
                ['key' => 'maintenance_mode'],
                ['value' => $on ? '1' : '0']
            );
            if (function_exists('setting_cache_clear')) {
                setting_cache_clear();
            }
        } catch (\Throwable $e) {
            // Maintenance flagging is best-effort; restore continues.
        }
    }

    /* =========================================================
       DATABASE
       ========================================================= */

    private function backupDatabase(string $targetDir): void
    {
        $driver = $this->dbConfig['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $this->backupSqlite($targetDir . '/database.sqlite');
            return;
        }

        if ($driver === 'mysql') {
            $this->backupMysql($targetDir . '/database.sql');
            return;
        }

        throw new RuntimeException('Unsupported database driver: ' . $driver);
    }

    /**
     * SQLite backup via VACUUM INTO (consistent snapshot, safe while the
     * database is in use). The snapshot is stored verbatim as a binary
     * SQLite database file — prepending anything would corrupt the header.
     */
    private function backupSqlite(string $targetFile): void
    {
        $path = $this->dbConfig['database'] ?? '';
        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('SQLite backups need a file-backed database.');
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $quoted = str_replace("'", "''", $targetFile);
        $pdo->exec("VACUUM INTO '$quoted'");
        $pdo = null;
    }

    /**
     * MySQL dump via PDO. The password never touches a command line;
     * the connection is made directly through PDO.
     */
    private function backupMysql(string $targetFile): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->dbConfig['host'] ?? '127.0.0.1',
            $this->dbConfig['port'] ?? '3306',
            $this->dbConfig['database'] ?? ''
        );

        $pdo = new PDO($dsn, (string) ($this->dbConfig['username'] ?? ''), (string) ($this->dbConfig['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $out = "-- PankhCMS MySQL backup (" . date('c') . ")\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $safeTable = str_replace('`', '``', (string) $table);

            $create = $pdo->query("SHOW CREATE TABLE `$safeTable`")->fetch(PDO::FETCH_ASSOC);
            $out .= "DROP TABLE IF EXISTS `$safeTable`;\n";
            $out .= ($create['Create Table'] ?? '') . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$safeTable`");
            $batch = [];
            foreach ($rows as $row) {
                $values = array_map(static function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($row));
                $batch[] = '(' . implode(',', $values) . ')';

                if (count($batch) >= 100) {
                    $out .= "INSERT INTO `$safeTable` VALUES\n" . implode(",\n", $batch) . ";\n";
                    $batch = [];
                }
            }
            if ($batch) {
                $out .= "INSERT INTO `$safeTable` VALUES\n" . implode(",\n", $batch) . ";\n";
            }
            $out .= "\n";
        }

        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($targetFile, $out);

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $pdo = null;
    }

    /* =========================================================
       FILES
       ========================================================= */

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            $target = $dst . '/' . $it->getSubPathName();
            if ($file->isDir()) {
                @mkdir($target, 0775, true);
            } else {
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                copy($file->getPathname(), $target);
            }
        }
    }

    /* =========================================================
       ZIP & UTILITIES
       ========================================================= */

    private function packageBackup(string $tmpDir, string $typeLabel): string
    {
        $filename = $this->backupDir . 'pankhcms-' . $typeLabel . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create backup ZIP.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $zip->addFile($file->getPathname(), $files->getSubPathName());
        }
        $zip->close();

        return $filename;
    }

    private function metadata(string $type, string $notes): array
    {
        return [
            'cms' => 'PankhCMS',
            'type' => $type,
            'date' => date('c'),
            'database' => $this->dbConfig['driver'] ?? 'sqlite',
            'php' => PHP_VERSION,
            'notes' => $notes,
        ];
    }

    private function cleanup(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($dir);
    }
}
