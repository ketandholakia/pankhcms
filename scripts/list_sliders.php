<?php
$c = parse_ini_file(__DIR__ . '/../.env');
$host = trim($c['DB_HOST'], '"');
$port = trim($c['DB_PORT'], '"');
$db = trim($c['DB_DATABASE'], '"');
$user = trim($c['DB_USERNAME'], '"');
$pass = trim($c['DB_PASSWORD'], '"');
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query('SELECT id, image_path, caption, active FROM slider_images ORDER BY sort_order');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "(no slider rows)\n";
        exit(0);
    }
    foreach ($rows as $r) {
        echo $r['id'] . ' | ' . ($r['image_path'] ?? '') . ' | active=' . ($r['active'] ?? '') . ' | caption=' . ($r['caption'] ?? '') . "\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
