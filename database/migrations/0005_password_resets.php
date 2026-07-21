<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('password_resets')) {
    $schema->create('password_resets', function ($t) {
        $t->increments('id');
        $t->integer('user_id')->unsigned();
        $t->string('token', 64)->unique();
        $t->timestamp('expires_at');
        $t->timestamps();

        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}
