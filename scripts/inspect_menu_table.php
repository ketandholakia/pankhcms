<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
require __DIR__ . '/../app/database.php';
$schema = \Illuminate\Database\Capsule\Manager::schema();
if (!$schema->hasTable('menu_items')) {
    echo "Table menu_items does not exist\n";
    exit;
}
$cols = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder()->getColumnListing('menu_items');
var_export($cols);
echo PHP_EOL;
$columns = \Illuminate\Database\Capsule\Manager::select("SHOW COLUMNS FROM menu_items");
var_export($columns);
echo PHP_EOL;
