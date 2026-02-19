<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
require __DIR__ . '/../app/database.php';
use App\Models\MenuItem;
// create test item
$item = MenuItem::create([
    'menu_id' => 1,
    'parent_id' => 1,
    'title' => 'CLI Test',
    'url' => null,
    'page_id' => null,
    'sort_order' => 999,
]);
if ($item) {
    echo "Created id={$item->id} parent_id={$item->parent_id}\n";
} else {
    echo "Create failed\n";
}
