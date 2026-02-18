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

    public function restorePage() {}
    public function restore() {}
}
