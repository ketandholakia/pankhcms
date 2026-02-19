<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
require __DIR__ . '/../app/database.php';
use App\Models\MenuItem;
$items = MenuItem::orderBy('id','desc')->take(10)->get();
foreach ($items as $i) {
    echo "id={$i->id} menu_id={$i->menu_id} parent_id={$i->parent_id} title={$i->title}\n";
}
