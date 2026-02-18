<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../../app/database.php";

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable("roles")) {
    $schema->create("roles", function ($t) {
        $t->increments("id");
        $t->string("name")->unique();
    });
    echo "✅ roles created\n";
}

if (!$schema->hasTable("permissions")) {
    $schema->create("permissions", function ($t) {
        $t->increments("id");
        $t->string("name")->unique();
    });
    echo "✅ permissions created\n";
}

if (!$schema->hasTable("role_permissions")) {
    $schema->create("role_permissions", function ($t) {
        $t->integer("role_id");
        $t->integer("permission_id");
    });
    echo "✅ role_permissions created\n";
}

if (!$schema->hasTable("user_roles")) {
    $schema->create("user_roles", function ($t) {
        $t->integer("user_id");
        $t->integer("role_id");
    });
    echo "✅ user_roles created\n";
}

echo "\n🎉 RBAC tables ready\n";
