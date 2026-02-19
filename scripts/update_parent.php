<?php
require __DIR__ . '/../vendor/autoload.php';
(Dotenv\Dotenv::createImmutable(__DIR__ . '/..'))->load();
require __DIR__ . '/../app/database.php';
$item = App\Models\MenuItem::find($argv[1] ?? null);
if (!$item) { echo "not found\n"; exit; }
$item->update(['parent_id' => (int)($argv[2] ?? 0)]);
echo "updated id={$item->id} parent_id={$item->parent_id}\n";
