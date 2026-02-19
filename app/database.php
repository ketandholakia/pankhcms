<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$projectRoot = dirname(__DIR__);

if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        $base = dirname(__DIR__);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}

$readEnv = static function (string $key, $default = null) {
    if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }

    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    return $default;
};

$driver = $readEnv('DB_CONNECTION', $readEnv('DB_DRIVER', 'sqlite'));

if ($driver === 'mysql') {
    $mysqlHost = $readEnv('DB_HOST', '127.0.0.1');

    if ($mysqlHost === 'localhost') {
        $mysqlHost = '127.0.0.1';
    }

    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => $mysqlHost,
        'port'      => $readEnv('DB_PORT', '3306'),
        'database'  => $readEnv('DB_DATABASE', 'flight_cms'),
        'username'  => $readEnv('DB_USERNAME', 'root'),
        'password'  => $readEnv('DB_PASSWORD', ''),
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'strict'    => true,
        'engine'    => null,
    ]);
} else {
    $sqliteDatabase = $readEnv('DB_DATABASE', 'database/database.sqlite');

    if (
        $sqliteDatabase !== ':memory:' &&
        strpos($sqliteDatabase, '?mode=memory') === false &&
        strpos($sqliteDatabase, '&mode=memory') === false
    ) {
        $isAbsoluteUnix = strpos($sqliteDatabase, '/') === 0;
        $isAbsoluteWindows = preg_match('/^[A-Za-z]:\\\\/', $sqliteDatabase) === 1;

        if (!$isAbsoluteUnix && !$isAbsoluteWindows) {
            $sqliteDatabase = $projectRoot . DIRECTORY_SEPARATOR . ltrim($sqliteDatabase, DIRECTORY_SEPARATOR);
        }

        $sqliteDir = dirname($sqliteDatabase);

        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0777, true);
        }

        if (!file_exists($sqliteDatabase)) {
            touch($sqliteDatabase);
        }
    }

    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => $sqliteDatabase,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
}

$capsule->setAsGlobal();
$capsule->bootEloquent();

if ($driver === 'sqlite') {
    $capsule->getConnection()->statement('PRAGMA foreign_keys = ON');
}
