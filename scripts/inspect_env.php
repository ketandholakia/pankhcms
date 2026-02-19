<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
require __DIR__ . '/../app/database.php';
echo "_ENV DB_CONNECTION=" . (array_key_exists('DB_CONNECTION', $_ENV) ? $_ENV['DB_CONNECTION'] : '<none>') . PHP_EOL;
echo "_ENV DB_DATABASE=" . (array_key_exists('DB_DATABASE', $_ENV) ? $_ENV['DB_DATABASE'] : '<none>') . PHP_EOL;
echo "getenv DB_DATABASE=" . (getenv('DB_DATABASE') ? getenv('DB_DATABASE') : '<none>') . PHP_EOL;
