<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('contact_messages')) {
    $schema->create('contact_messages', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->string('subject')->nullable();
        $table->text('message');
        $table->string('ip', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    echo "contact_messages table created.\n";
    exit;
}

echo "contact_messages table already exists.\n";
