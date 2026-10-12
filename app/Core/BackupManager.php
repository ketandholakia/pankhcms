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

    /** Maximum uncompressed size of the database entry we will load for restore. */
    private static int $maxDatabaseEntryBytes = 1073741824; // 1 GiB

    /** Whether maintenance mode was already on when a restore started. */
    private bool $maintenanceWasEnabled = false;

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

        $validated = $this->validateBackupZip($zipPath);

        $this->setMaintenance(true);

        try {
            if ($this->dbConfig['driver'] === 'sqlite') {
                $this->restoreSqlite($validated['content']);
            } elseif ($this->dbConfig['driver'] === 'mysql') {
                $this->restoreMysql($validated['content']);
            } else {
                throw new RuntimeException('Unsupported database driver: ' . $this->dbConfig['driver']);
            }
        } finally {
            $this->setMaintenance(false);
        }
    }

    /**
     * Validate a backup ZIP without touching any database. Returns the
     * validated database entry name and its content.
     *
     * @return array{entry: string, content: string}
     */
    public function validateBackupZip(string $zipPath): array
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
                if (in_array($name, $names, true)) {
                    // Duplicate names are ambiguous: getFromName() would
                    // silently restore whichever copy comes first.
                    throw new RuntimeException('The backup ZIP contains duplicate entry names.');
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

            // Refuse to load an oversized database entry into memory.
            $entryIndex = (int) array_search($entry, $names, true);
            $entrySize = (int) ($zip->statIndex($entryIndex)['size'] ?? 0);
            if ($entrySize > self::$maxDatabaseEntryBytes) {
                throw new RuntimeException('The database entry in the backup is too large to restore.');
            }

            return ['entry' => $entry, 'content' => (string) $zip->getFromName($entry)];
        } finally {
            $zip->close();
        }
    }

    /**
     * Restore with automatic recovery: immediately before restoring, a
     * verified backup of the live database is taken; if the requested
     * restore fails, that backup is restored to return the database to
     * its pre-restore state.
     *
     * If the recovery attempt itself fails, maintenance mode is forced ON,
     * an operator-instructions file is written next to the recovery archive
     * (which is always kept), and both errors are logged and re-thrown.
     *
     * The restore is aborted up front — without touching the database —
     * when a recovery backup cannot be created or verified.
     */
    public function restoreFromZipWithRecovery(string $zipPath): void
    {
        $label = basename($zipPath);

        // The safety net must exist and be restorable BEFORE anything is
        // modified. Both steps throw without database side effects.
        try {
            $recoveryPath = $this->createDatabaseBackup('Pre-restore recovery backup before restoring ' . $label);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Restore aborted: could not create a recovery backup of the current database ('
                . $e->getMessage() . '). The database was not modified. Fix the database state or restore '
                . 'manually by importing the database dump from the archive.',
                0,
                $e
            );
        }

        try {
            $this->validateBackupZip($recoveryPath);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Restore aborted: the recovery backup failed validation ('
                . $e->getMessage() . '). The database was not modified. The recovery backup is kept at '
                . basename($recoveryPath) . ' for manual inspection.',
                0,
                $e
            );
        }

        try {
            $this->restoreFromZip($zipPath);
            return;
        } catch (\Throwable $restoreError) {
            error_log("BackupManager: restore of $label failed: " . $restoreError->getMessage()
                . ' — attempting recovery from ' . basename($recoveryPath));

            try {
                $this->restoreFromZip($recoveryPath);
            } catch (\Throwable $recoveryError) {
                error_log("BackupManager: RECOVERY FAILED from " . basename($recoveryPath) . ': '
                    . $recoveryError->getMessage() . ' — the database may be left in the partially '
                    . 'restored state. Operator action required.');
                $this->enableMaintenanceMode();
                $this->writeRestoreFailureInstructions($zipPath, $recoveryPath, $restoreError, $recoveryError);

                throw new RuntimeException(
                    'Restore failed AND automatic recovery failed. The site is in maintenance mode. '
                    . 'Recovery backup: ' . basename($recoveryPath)
                    . ' (kept in storage/backups — download it and import the database dump manually). '
                    . 'Original restore error: ' . $restoreError->getMessage()
                    . ' | Recovery error: ' . $recoveryError->getMessage(),
                    0,
                    $recoveryError
                );
            }

            error_log("BackupManager: database recovered to its pre-restore state from "
                . basename($recoveryPath));
            throw new RuntimeException(
                'Restore failed; the database was recovered to its pre-restore state. '
                . 'Original restore error: ' . $restoreError->getMessage(),
                0,
                $restoreError
            );
        }
    }

    /**
     * Force maintenance mode on regardless of any previous state. Used
     * when a restore fails and recovery fails: the site must not serve
     * traffic against a partially restored database.
     */
    public function enableMaintenanceMode(): void
    {
        $this->setMaintenance(true);
    }

    private function writeRestoreFailureInstructions(string $requestedPath, string $recoveryPath, \Throwable $restoreError, \Throwable $recoveryError): void
    {
        $lines = [
            'PankhCMS restore failure — operator action required',
            'Date: ' . date('c'),
            '',
            'The requested restore failed AND the automatic recovery failed.',
            'The database may be left in a partially restored state.',
            'Maintenance mode is ON; the site is not serving traffic.',
            '',
            'Requested archive : ' . basename($requestedPath),
            'Recovery archive  : ' . basename($recoveryPath) . ' (kept in storage/backups/)',
            'Restore error     : ' . $restoreError->getMessage(),
            'Recovery error    : ' . $recoveryError->getMessage(),
            '',
            'Recovery options:',
            '1. Download the recovery archive from Admin > Backups and import the',
            '   database dump manually (phpMyAdmin / mysql CLI for database.sql,',
            '   or copy database.sqlite over the database file for SQLite).',
            '2. When the database is healthy again, exit maintenance mode:',
            '   UPDATE settings SET value = \'0\' WHERE `key` = \'maintenance_mode\';',
            '   (or Admin > Settings) and delete this file.',
        ];
        @file_put_contents(
            $this->backupDir . 'RESTORE-FAILED-' . date('Ymd-His') . '.txt',
            implode("\n", $lines)
        );
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

        // Write to a sibling temp file first, then validate it as a WORKING
        // database before anything is replaced. A snapshot with a valid
        // header but garbage content must never reach the live path.
        $tmp = $path . '.restore-' . uniqid();
        if (file_put_contents($tmp, $snapshot) === false) {
            @unlink($tmp);
            throw new RuntimeException('Failed to write the restored database file.');
        }

        try {
            $check = new PDO('sqlite:' . $tmp, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $result = (string) $check->query('PRAGMA integrity_check')->fetchColumn();
            $check = null;
            if ($result !== 'ok') {
                throw new RuntimeException('The database snapshot failed the SQLite integrity check.');
            }
        } catch (\PDOException $e) {
            // NB: PDOException extends RuntimeException, so this must be
            // caught before the RuntimeException re-throw below — a corrupt
            // snapshot surfaces as a PDOException on first read.
            @unlink($tmp);
            throw new RuntimeException('The database snapshot is not a readable SQLite database.');
        } catch (RuntimeException $e) {
            @unlink($tmp);
            throw $e;
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw new RuntimeException('The database snapshot is not a readable SQLite database.');
        }

        // Keep the permissions of the database being replaced (POSIX).
        if (is_file($path)) {
            @chmod($tmp, fileperms($path) & 0777);
        }

        if (!rename($tmp, $path)) {
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
            if ($on) {
                // Remember whether the site was already in maintenance so
                // the cleanup below restores that state instead of blindly
                // switching it off over the admin's head.
                $previous = $capsule::table('settings')->where('key', 'maintenance_mode')->value('value');
                $this->maintenanceWasEnabled = (string) $previous === '1';
                $capsule::table('settings')->updateOrInsert(
                    ['key' => 'maintenance_mode'],
                    ['value' => '1']
                );
            } else {
                $capsule::table('settings')->updateOrInsert(
                    ['key' => 'maintenance_mode'],
                    ['value' => $this->maintenanceWasEnabled ? '1' : '0']
                );
            }
            if (function_exists('setting_cache_clear')) {
                setting_cache_clear();
            }
            // Maintenance state changed: anonymous page-cache hits are
            // served before bootstrap, so they must never outlive a
            // maintenance toggle.
            if (class_exists(\App\Middleware\PageCache::class)) {
                \App\Middleware\PageCache::clear();
            }
        } catch (\Throwable $e) {
            // Maintenance flagging is best-effort; restore continues. Log
            // loudly: a failure on the OFF path leaves the site in
            // maintenance mode and needs a human.
            error_log('BackupManager: could not ' . ($on ? 'enable' : 'disable')
                . ' maintenance mode: ' . $e->getMessage());
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
