<?php

/**
 * Retention job: delete page view tracking rows older than 90 days.
 *
 * Usage:
 *   php scripts/prune-page-views.php
 *   php scripts/prune-page-views.php --days=30
 *
 * Schedule daily via cron (Linux):
 *   0 3 * * * cd /path/to/pankhcms && php scripts/prune-page-views.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$days = 90;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--days=')) {
        $days = max(1, (int) substr($arg, 7));
    }
}

$cutoff = date('Y-m-d H:i:s', time() - $days * 86400);

try {
    if (!Capsule::schema()->hasTable('page_views')) {
        echo "page_views table does not exist; nothing to prune.\n";
        exit(0);
    }

    $deleted = Capsule::table('page_views')->where('created_at', '<', $cutoff)->delete();
    echo "Deleted {$deleted} page view(s) older than {$days} days (before {$cutoff}).\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Prune failed: ' . $e->getMessage() . "\n");
    exit(1);
}
