<?php
// Run this script to create the media table
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->create('media', function ($table) {
    $table->increments('id');
    $table->string('filename');
    $table->string('original_name')->nullable();
    $table->string('mime_type')->nullable();
    $table->integer('size')->nullable();
    $table->string('url');
    $table->integer('user_id')->nullable();
    $table->string('alt')->nullable();
    $table->string('title')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();
});

echo "Media table created.\n";
