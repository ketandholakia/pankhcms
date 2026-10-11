<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Optional expiry on API tokens; null means the token never expires.
$schema = Capsule::schema();

if (!$schema->hasTable('api_tokens')) {
    return;
}

if (!$schema->hasColumn('api_tokens', 'expires_at')) {
    $schema->table('api_tokens', function ($t) {
        $t->timestamp('expires_at')->nullable();
    });
}
