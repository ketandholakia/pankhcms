<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Align existing page_views tables with the schema VisitTracker writes:
// path/source/referrer/ip columns (older installs had ip_address +
// view_date instead).
$schema = Capsule::schema();

if (!$schema->hasTable('page_views')) {
    return;
}

if (!$schema->hasColumn('page_views', 'ip')) {
    $schema->table('page_views', function ($t) {
        $t->string('ip', 45)->nullable();
    });
}

foreach (['path' => 'string', 'source' => 'string', 'referrer' => 'text'] as $column => $type) {
    if (!$schema->hasColumn('page_views', $column)) {
        $schema->table('page_views', function ($t) use ($column, $type) {
            $t->$type($column)->nullable();
        });
    }
}

if (!$schema->hasColumn('page_views', 'created_at')) {
    $schema->table('page_views', function ($t) {
        $t->dateTime('created_at')->nullable();
    });
}
