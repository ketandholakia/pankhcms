<?php
// Migration script to create slider_images table
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->create('slider_images', function ($table) {
    $table->increments('id');
    $table->string('image_path');
    $table->string('caption')->nullable();
    $table->string('link')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('active')->default(1);
    $table->timestamps();
});

echo "Slider images table created.\n";
