<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../database/database.sqlite',
    'prefix' => '',
    'foreign_key_constraints' => true,
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

$capsule->getConnection()->statement('PRAGMA foreign_keys = ON');
