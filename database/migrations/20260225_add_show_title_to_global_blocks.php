<?php
// Migration to add `show_title` boolean column to `global_blocks` if missing.

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

if (!$schema->hasTable('global_blocks')) {
    echo "Table global_blocks does not exist; nothing to migrate.\n";
    exit(0);
}

if (!$schema->hasColumn('global_blocks', 'show_title')) {
    echo "Adding show_title column to global_blocks...\n";
    try {
        $schema->table('global_blocks', function ($t) {
            $t->boolean('show_title')->default(true)->after('title');
        });
        echo "Done.\n";
    } catch (Throwable $e) {
        echo "Failed: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Column show_title already exists.\n";
}

return 0;
