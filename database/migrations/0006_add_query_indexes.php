<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$connection = Capsule::connection();
$driver = $connection->getDriverName();

$statements = [];

if ($driver === 'sqlite') {
    $statements = [
        'CREATE INDEX IF NOT EXISTS idx_pages_status_published_at ON pages (status, published_at)',
        'CREATE INDEX IF NOT EXISTS idx_pages_parent_id ON pages (parent_id)',
        'CREATE INDEX IF NOT EXISTS idx_pages_type ON pages (type)',
        'CREATE INDEX IF NOT EXISTS idx_menu_items_menu_id ON menu_items (menu_id)',
        'CREATE INDEX IF NOT EXISTS idx_menu_items_parent_id ON menu_items (parent_id)',
        'CREATE INDEX IF NOT EXISTS idx_menu_items_page_id ON menu_items (page_id)',
    ];
} else {
    $statements = [
        'CREATE INDEX idx_pages_status_published_at ON pages (status, published_at)',
        'CREATE INDEX idx_pages_parent_id ON pages (parent_id)',
        'CREATE INDEX idx_pages_type ON pages (type)',
        'CREATE INDEX idx_menu_items_menu_id ON menu_items (menu_id)',
        'CREATE INDEX idx_menu_items_parent_id ON menu_items (parent_id)',
        'CREATE INDEX idx_menu_items_page_id ON menu_items (page_id)',
    ];
}

foreach ($statements as $sql) {
    try {
        $connection->statement($sql);
    } catch (\Throwable $e) {
        // Ignore duplicate/existing index errors so the migration stays idempotent.
    }
}
