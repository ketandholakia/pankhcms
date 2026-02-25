<?php
// Migration script for global_blocks and block_placements tables
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('global_blocks')) {
    $schema->create('global_blocks', function ($table) {
        $table->increments('id');
        $table->string('name', 150);
        $table->string('slug', 150)->unique();
        $table->string('type', 100);
        $table->json('content');
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->dateTime('created_at')->nullable();
        $table->dateTime('updated_at')->nullable();
    });
    echo "global_blocks table created.\n";
}

if (!$schema->hasTable('block_placements')) {
    $schema->create('block_placements', function ($table) {
        $table->increments('id');
        $table->integer('block_id');
        $table->string('location', 100);
        $table->integer('sort_order')->default(0);
    });
    echo "block_placements table created.\n";
}
