<?php
// Migration script to create content_type_fields table for custom fields

require_once __DIR__ . '/../app/database.php';

$db = getDb();

$sql = "
CREATE TABLE IF NOT EXISTS content_type_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content_type_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'text',
    options TEXT DEFAULT NULL, -- For select, radio, etc (JSON)
    required TINYINT(1) DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(content_type_id) REFERENCES content_types(id) ON DELETE CASCADE
);
";

if ($db->exec($sql) !== false) {
    echo "Migration successful: content_type_fields table created.\n";
} else {
    echo "Migration failed.\n";
}
