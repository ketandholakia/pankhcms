<?php

namespace App\Controllers\Admin;

use Flight;
use App\Core\BackupManager;

class BackupController
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = dirname(__DIR__, 3) . '/storage/backups/';
    }

    /**
     * Show backup page
     */
    public function index()
    {
        $files = glob($this->backupDir . '*.zip') ?: [];

        echo Flight::get('blade')->render('admin.backups.index', [
            'backups' => $files
        ]);
    }

    /**
     * Create backup
     */
    public function create()
    {
        $config = require dirname(__DIR__, 3) . '/config/database.php';

        $manager = new BackupManager(
            $config['connections'][$config['default']]
        );

        $type  = $_POST['type']  ?? 'full';
        $notes = $_POST['notes'] ?? '';

        if ($type === 'database') {
            $manager->createDatabaseBackup($notes);
        } else {
            $manager->createFullBackup($notes);
        }

        Flight::redirect('/admin/backups');
    }

    /**
     * Download backup
     */
    public function download($filename)
    {
        $file = $this->backupDir . basename($filename);

        if (!file_exists($file)) {
            Flight::halt(404, 'Backup not found');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }

    /**
     * Delete backup
     */
    public function delete($filename)
    {
        $file = $this->backupDir . basename($filename);

        if (file_exists($file)) {
            unlink($file);
        }

        Flight::redirect('/admin/backups');
    }

    /**
     * Confirmation page for restoring a backup (database only).
     */
    public function restorePage()
    {
        $filename = basename((string) ($_GET['file'] ?? ''));
        $file = $this->backupDir . $filename;

        if ($filename === '' || !is_file($file)) {
            Flight::redirect('/admin/backups?error=backup_not_found');
            return;
        }

        echo Flight::get('blade')->render('admin.backups.restore', ['filename' => $filename]);
    }

    /**
     * Restore the database from the given backup after explicit
     * confirmation. A verified recovery backup of the live database is
     * taken first; if the restore fails, the database is automatically
     * returned to its pre-restore state. Maintenance mode is enabled for
     * the duration.
     */
    public function restore()
    {
        $filename = basename((string) ($_POST['file'] ?? ''));
        $file = $this->backupDir . $filename;

        if ($filename === '' || !is_file($file)) {
            Flight::redirect('/admin/backups?error=backup_not_found');
            return;
        }

        if (($_POST['confirm'] ?? '') !== 'yes') {
            Flight::redirect('/admin/backups/restore?file=' . urlencode($filename) . '&error=confirm');
            return;
        }

        $config = require dirname(__DIR__, 3) . '/config/database.php';

        try {
            $manager = new BackupManager(
                $config['connections'][$config['default']]
            );
            $manager->restoreFromZipWithRecovery($file);

            Flight::redirect('/admin/backups?status=restored');
        } catch (\Throwable $e) {
            error_log('Backup restore failed: ' . $e->getMessage());
            Flight::redirect('/admin/backups/restore?file=' . urlencode($filename) . '&error=restore_failed');
        }
    }
}
