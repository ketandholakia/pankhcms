<?php

namespace App\Core;

use ZipArchive;
use Exception;

class BackupManager
{
    /**
     * Create a database-only backup (structure + data)
     */
    public function createDatabaseBackup(string $notes = 'Database backup'): string
    {
        $meta   = $this->getMetadata('database', $notes);
        $tmpDir = sys_get_temp_dir() . '/pankhcms_db_backup_' . uniqid();
        mkdir($tmpDir, 0775, true);
        $this->backupDatabase($tmpDir);
        file_put_contents(
            $tmpDir . '/backup.json',
            json_encode($meta, JSON_PRETTY_PRINT)
        );
        $zipPath = $this->packageBackup($tmpDir);
        $this->cleanup($tmpDir);
        return $zipPath;
    }
    protected string $backupDir;
    protected array $dbConfig;
    protected string $appVersion = '1.0.0';

    public function __construct(array $dbConfig)
    {
        $this->backupDir = __DIR__ . '/../../storage/backups/';
        $this->dbConfig  = $dbConfig;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0775, true);
        }
    }

    /* =========================================================
       CREATE BACKUPS
       ========================================================= */

    public function createFullBackup(string $notes = 'Full backup'): string
    {
        $meta   = $this->getMetadata('full', $notes);
        $tmpDir = sys_get_temp_dir() . '/pankhcms_backup_' . uniqid();

        mkdir($tmpDir, 0775, true);

        $this->backupDatabase($tmpDir);

        $filesDir = $tmpDir . '/files';
        mkdir($filesDir, 0775, true);
        $this->backupFiles($filesDir);

        file_put_contents(
            $tmpDir . '/backup.json',
            json_encode($meta, JSON_PRETTY_PRINT)
        );

        $zipPath = $this->packageBackup($tmpDir);
        $this->cleanup($tmpDir);

        return $zipPath;
    }

    /* =========================================================
       RESTORE BACKUP
       ========================================================= */

    public function restoreBackup(string $zipFile): bool
    {
        if (!file_exists($zipFile)) {
            throw new Exception('Backup file not found');
        }

        $tmpDir = sys_get_temp_dir() . '/pankhcms_restore_' . uniqid();
        mkdir($tmpDir, 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception('Cannot open backup ZIP');
        }

        $zip->extractTo($tmpDir);
        $zip->close();

        $metaFile = $tmpDir . '/backup.json';
        if (!file_exists($metaFile)) {
            throw new Exception('Invalid backup: missing metadata');
        }

        $meta = json_decode(file_get_contents($metaFile), true);

        $this->setMaintenance(true);

        try {
            $this->restoreDatabase($tmpDir, $meta['database'] ?? 'sqlite');
            $this->restoreFiles($tmpDir . '/files');
        } finally {
            $this->setMaintenance(false);
            $this->cleanup($tmpDir);
        }

        return true;
    }

    /* =========================================================
       DATABASE
       ========================================================= */

    protected function backupDatabase(string $targetDir): void
    {
        $type = $this->dbConfig['driver'] ?? 'sqlite';

        if ($type === 'sqlite') {
            copy(
                $this->dbConfig['database'],
                $targetDir . '/database.sqlite'
            );
            return;
        }

        if ($type === 'mysql') {
            $mysqli = new \mysqli(
                $this->dbConfig['host'] ?? 'localhost',
                $this->dbConfig['username'],
                $this->dbConfig['password'],
                $this->dbConfig['database']
            );
            if ($mysqli->connect_errno) {
                throw new Exception('MySQL connection failed: ' . $mysqli->connect_error);
            }

            $sql = "";
            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                // Structure
                $res = $mysqli->query("SHOW CREATE TABLE `" . $table . "`");
                $row = $res->fetch_assoc();
                $sql .= "\n-- Table structure for `{$table}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $row['Create Table'] . ";\n\n";

                // Data
                $res = $mysqli->query("SELECT * FROM `" . $table . "`");
                if ($res->num_rows > 0) {
                    $sql .= "-- Dumping data for `{$table}`\n";
                    while ($row = $res->fetch_assoc()) {
                        $vals = array_map(function($v) use ($mysqli) {
                            return isset($v) ? "'" . $mysqli->real_escape_string($v) . "'" : "NULL";
                        }, array_values($row));
                        $sql .= "INSERT INTO `{$table}` VALUES(" . implode(",", $vals) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            file_put_contents($targetDir . '/database.sql', $sql);
            $mysqli->close();
            return;
        }

        throw new Exception('Unsupported database type');
    }

    protected function restoreDatabase(string $tmpDir, string $type): void
    {
        if ($type === 'sqlite') {
            copy(
                $tmpDir . '/database.sqlite',
                $this->dbConfig['database']
            );
            return;
        }

        if ($type === 'mysql') {
            $cmd = sprintf(
                'mysql -h%s -u%s -p%s %s < %s',
                escapeshellarg($this->dbConfig['host'] ?? 'localhost'),
                escapeshellarg($this->dbConfig['username']),
                escapeshellarg($this->dbConfig['password']),
                escapeshellarg($this->dbConfig['database']),
                escapeshellarg($tmpDir . '/database.sql')
            );

            exec($cmd, $o, $result);

            if ($result !== 0) {
                throw new Exception('MySQL restore failed');
            }
            return;
        }

        throw new Exception('Unsupported database type');
    }

    /* =========================================================
       FILES
       ========================================================= */

    protected function backupFiles(string $targetDir): void
    {
        $paths = [
            'storage' => '/storage/',
            'themes'  => '/themes/',
            'uploads' => '/public/uploads/',
        ];

        foreach ($paths as $name => $rel) {
            $src = realpath(__DIR__ . '/../../' . ltrim($rel, '/'));

            if ($src && is_dir($src)) {
                $this->copyDir($src, $targetDir . '/' . $name);
            }
        }
    }

    protected function restoreFiles(string $filesDir): void
    {
        $targets = [
            'storage' => 'storage',
            'themes'  => 'themes',
            'uploads' => 'public/uploads',
        ];

        foreach ($targets as $src => $dst) {
            $srcPath = $filesDir . '/' . $src;
            $dstPath = __DIR__ . '/../../' . $dst;

            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath);
            }
        }
    }

    /* =========================================================
       ZIP & UTILITIES
       ========================================================= */

    protected function packageBackup(string $srcDir): string
    {
        $zip = new ZipArchive();

        // Get site_name from settings and sanitize for filename
        $siteName = setting('site_name', 'pankhcms');
        $safeSiteName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($siteName));
        $safeSiteName = rtrim($safeSiteName, '_');

        // Detect backup type from backup.json
        $backupType = 'full';
        $metaFile = $srcDir . '/backup.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if (!empty($meta['type'])) {
                $backupType = $meta['type'];
            }
        }

        $typeLabel = ($backupType === 'database') ? 'db-backup' : 'full-backup';

        $filename = $this->backupDir .
            $safeSiteName . '-' . $typeLabel . '-' . date('Ymd-His') . '.zip';

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create ZIP');
        }

        $this->addDirToZip($srcDir, $zip);
        $zip->close();

        return $filename;
    }

    protected function addDirToZip(string $dir, ZipArchive $zip): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $zip->addFile(
                $file,
                substr($file, strlen($dir) + 1)
            );
        }
    }

    protected function copyDir(string $src, string $dst): void
    {
        @mkdir($dst, 0775, true);

        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') continue;

            $s = "$src/$file";
            $d = "$dst/$file";

            is_dir($s)
                ? $this->copyDir($s, $d)
                : copy($s, $d);
        }
    }

    protected function cleanup(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir()
                ? rmdir($file)
                : unlink($file);
        }

        rmdir($dir);
    }

    protected function setMaintenance(bool $on): void
    {
        $flag = __DIR__ . '/../../storage/maintenance.flag';

        $on
            ? file_put_contents($flag, 'Maintenance mode')
            : @unlink($flag);
    }

    protected function getMetadata(string $type, string $notes): array
    {
        return [
            'cms'      => 'PankhCMS',
            'version'  => $this->appVersion,
            'date'     => gmdate('c'),
            'database' => $this->dbConfig['driver'] ?? 'sqlite',
            'php'      => phpversion(),
            'type'     => $type,
            'notes'    => $notes,
        ];
    }
}
