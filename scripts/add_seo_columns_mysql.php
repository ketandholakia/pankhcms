<?php
// Load .env manually
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        putenv(trim($k) . '=' . trim($v));
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_DATABASE') ?: 'pankhcms';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$toAdd = [
    'seo_title'       => 'TEXT NULL',
    'seo_description' => 'TEXT NULL',
    'seo_keywords'    => 'TEXT NULL',
    'seo_image'       => 'TEXT NULL',
];

$existingRows  = $pdo->query('SHOW COLUMNS FROM pages')->fetchAll(PDO::FETCH_ASSOC);
$existingNames = array_column($existingRows, 'Field');

foreach ($toAdd as $col => $def) {
    if (!in_array($col, $existingNames)) {
        $pdo->exec("ALTER TABLE pages ADD COLUMN `$col` $def");
        echo "Added: $col\n";
    } else {
        echo "Already exists: $col\n";
    }
}

echo "Done.\n";
