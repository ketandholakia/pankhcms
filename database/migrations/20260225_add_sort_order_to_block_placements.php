<?php
// Simple migration script to add `sort_order` column to `block_placements` if missing.

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$root = realpath(__DIR__ . '/..');
$envPath = $root . '/../.env';
if (!file_exists($envPath)) {
    echo ".env not found; please run installer or ensure environment is configured.\n";
    exit(1);
}

// Load .env into $_ENV (very small parser)
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v, " \t\n\r\0\x0B\"");
    $_ENV[$k] = $v;
    putenv("$k=$v");
}

$capsule = new Capsule();
$dbDriver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
if ($dbDriver === 'mysql') {
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? '',
        'username' => $_ENV['DB_USERNAME'] ?? '',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
} else {
    $dbPath = realpath(__DIR__ . '/../database.sqlite') ?: (__DIR__ . '/../database.sqlite');
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ]);
}

$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = $capsule->schema();

if (!$schema->hasTable('block_placements')) {
    echo "Table block_placements does not exist; nothing to migrate.\n";
    exit(0);
}

// Add sort_order column if missing
if (!$schema->hasColumn('block_placements', 'sort_order')) {
    echo "Adding sort_order column to block_placements...\n";
    try {
        $schema->table('block_placements', function ($t) {
            $t->integer('sort_order')->default(0)->after('order');
        });
        echo "Done.\n";
    } catch (Throwable $e) {
        echo "Failed: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Column sort_order already exists.\n";
}

return 0;
