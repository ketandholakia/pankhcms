<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasColumn('pages', 'published_at')) {
    $schema->table('pages', function ($t) {
        $t->timestamp('published_at')->nullable();
        $t->timestamp('published_notified_at')->nullable();
    });
}

// Backfill: existing pages keep current behavior (visible now)
Capsule::table('pages')
    ->whereNull('published_at')
    ->where('status', 'published')
    ->update(['published_at' => Capsule::raw('created_at')]);
