<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Create migrations table if it doesn't exist
$schema = Capsule::schema();
if (!$schema->hasTable('migrations')) {
    $schema->create('migrations', function ($t) {
        $t->increments('id');
        $t->string('migration');
        $t->integer('batch');
        $t->timestamp('created_at')->useCurrent();
    });
    echo "Created migrations table.\n";
}

$executed = Capsule::table('migrations')->pluck('migration')->toArray();
$files = glob(__DIR__ . '/../database/migrations/*.php');
$batch = Capsule::table('migrations')->max('batch') + 1;
$migratedAny = false;

foreach ($files as $file) {
    $filename = basename($file, '.php');
    if (in_array($filename, $executed)) {
        continue;
    }
    
    echo "Migrating: $filename\n";
    
    // We isolate execution to not leak variables, but pass Capsule
    call_user_func(function() use ($file, $schema) {
        require $file;
    });
    
    Capsule::table('migrations')->insert([
        'migration' => $filename,
        'batch' => $batch
    ]);
    
    echo "Migrated:  $filename\n";
    $migratedAny = true;
}

if (!$migratedAny) {
    echo "Nothing to migrate.\n";
}
