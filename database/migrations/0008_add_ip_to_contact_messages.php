<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// The installer schema already has ip/user_agent on contact_messages;
// bring the migrations in line so the columns exist everywhere.
$schema = Capsule::schema();

if (!$schema->hasColumn('contact_messages', 'ip')) {
    $schema->table('contact_messages', function ($t) {
        $t->string('ip', 45)->nullable();
    });
}

if (!$schema->hasColumn('contact_messages', 'user_agent')) {
    $schema->table('contact_messages', function ($t) {
        $t->text('user_agent')->nullable();
    });
}
