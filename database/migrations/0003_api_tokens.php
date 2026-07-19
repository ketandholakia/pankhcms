<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('api_tokens')) {
    $schema->create('api_tokens', function ($t) {
        $t->increments('id');
        $t->integer('user_id')->unsigned();
        $t->string('name');
        $t->string('token', 64)->unique();
        $t->timestamp('last_used_at')->nullable();
        $t->timestamps();

        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}
