<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Align existing global_blocks tables with what the application reads and
// writes: GlobalBlockController stores a `type` (template selector) and a
// `status` (visibility gate used by blocks()/blocks_html()); migration
// 0001 originally created neither, so on installs that ran the old 0001
// creating a global block failed and no block could ever render.
//
// Fresh installs skip this migration: 0001 now creates both columns
// directly, and the hasColumn guards below make this migration a no-op
// for them (safe under re-run conventions).
$schema = Capsule::schema();

if (!$schema->hasTable('global_blocks')) {
    return;
}

if (!$schema->hasColumn('global_blocks', 'type')) {
    $schema->table('global_blocks', function ($t) {
        $t->string('type')->default('text');
    });
}

if (!$schema->hasColumn('global_blocks', 'status')) {
    $schema->table('global_blocks', function ($t) {
        $t->boolean('status')->default(true);
    });
}
