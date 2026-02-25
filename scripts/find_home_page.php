<?php
$c = parse_ini_file(__DIR__ . '/../.env');
$host = trim($c['DB_HOST'], '"');
$port = trim($c['DB_PORT'], '"');
$db = trim($c['DB_DATABASE'], '"');
$user = trim($c['DB_USERNAME'], '"');
$pass = trim($c['DB_PASSWORD'], '"');
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('SELECT id, title, slug, type FROM pages WHERE slug = ? LIMIT 1');
    $stmt->execute(['home']);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        echo "Found page: id={$r['id']} title={$r['title']} slug={$r['slug']} type={$r['type']}\n";
    } else {
        echo "No page with slug 'home'\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
