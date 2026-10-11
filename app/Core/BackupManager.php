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
       DATABASE
       ========================================================= */

    private function backupDatabase(string $targetDir): void
    {
        $driver = $this->dbConfig['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $this->backupSqlite($targetDir . '/database.sql');
            return;
        }

        if ($driver === 'mysql') {
            $this->backupMysql($targetDir . '/database.sql');
            return;
        }

        throw new RuntimeException('Unsupported database driver: ' . $driver);
    }

    /**
     * SQLite dump via VACUUM INTO (consistent snapshot, safe while the
     * database is in use).
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

        $sql = "-- PankhCMS SQLite backup (" . date('c') . ")\n"
            . "-- Original file: " . $path . "\n"
            . "-- Restored by copying this file over the database file.\n";
        file_put_contents($targetFile, $sql . file_get_contents($targetFile));
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
