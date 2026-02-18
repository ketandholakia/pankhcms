<?php

namespace App\Core;

use ZipArchive;
use Exception;

class BackupManager
{
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
            $cmd = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                escapeshellarg($this->dbConfig['host'] ?? 'localhost'),
                escapeshellarg($this->dbConfig['username']),
                escapeshellarg($this->dbConfig['password']),
                escapeshellarg($this->dbConfig['database']),
                escapeshellarg($targetDir . '/database.sql')
            );

            exec($cmd, $o, $result);

            if ($result !== 0) {
                throw new Exception('MySQL dump failed');
            }
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

        $filename = $this->backupDir .
            'pankhcms-backup-' . date('Ymd-His') . '.zip';

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
