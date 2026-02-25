<?php
$c = parse_ini_file(__DIR__ . '/../.env');
$host = trim($c['DB_HOST'], '"');
$port = trim($c['DB_PORT'], '"');
$db = trim($c['DB_DATABASE'], '"');
$user = trim($c['DB_USERNAME'], '"');
$pass = trim($c['DB_PASSWORD'], '"');
$homepageId = 16; // page id found earlier
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    $stmt->execute(['homepage_id', (string)$homepageId]);
    echo "Set homepage_id to {$homepageId}\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
