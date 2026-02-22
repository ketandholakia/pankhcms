<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('page_views')) {
    $schema->create('page_views', function ($t) {
        $t->bigIncrements('id');
        $t->integer('page_id')->nullable();
        $t->string('path')->nullable();
        $t->string('source')->nullable();
        $t->text('referrer')->nullable();
        $t->string('ip', 45)->nullable();
        $t->text('user_agent')->nullable();
        $t->string('session_id')->nullable();
        $t->dateTime('created_at')->nullable();

        $t->foreign('page_id')->references('id')->on('pages')->onDelete('set null');
    });

    echo "✅ page_views table created\n";
} else {
    echo "ℹ️ page_views table already exists\n";
}

try {
    Capsule::connection()->statement('CREATE INDEX IF NOT EXISTS idx_page_views_created_at ON page_views(created_at)');
    Capsule::connection()->statement('CREATE INDEX IF NOT EXISTS idx_page_views_page_id ON page_views(page_id)');
    Capsule::connection()->statement('CREATE INDEX IF NOT EXISTS idx_page_views_source ON page_views(source)');
    echo "✅ page_views indexes ready\n";
} catch (\Throwable $e) {
    echo "⚠️ Index creation skipped: {$e->getMessage()}\n";
}
