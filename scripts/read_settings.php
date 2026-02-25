<?php
$c = parse_ini_file(__DIR__ . '/../.env');
$host = trim($c['DB_HOST'], '"');
$port = trim($c['DB_PORT'], '"');
$db = trim($c['DB_DATABASE'], '"');
$user = trim($c['DB_USERNAME'], '"');
$pass = trim($c['DB_PASSWORD'], '"');
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('SELECT `key`,`value` FROM settings WHERE `key` IN (?,?)');
    $stmt->execute(['homepage_id', 'site_name']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "(no rows)\n";
        exit(0);
    }
    foreach ($rows as $r) {
        echo $r['key'] . " => " . $r['value'] . "\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
