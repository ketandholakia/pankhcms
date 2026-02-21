<?php
// Migration script to create product_galleries table
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->create('product_galleries', function ($table) {
    $table->increments('id');
    $table->string('title');
    $table->string('image_path');
    $table->string('caption')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('active')->default(1);
    $table->timestamps();
});

echo "Product gallery table created.\n";
