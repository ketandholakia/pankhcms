<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Align existing slider_images tables with the application: the homepage
// slider query (SiteController::home) and SliderController both use an
// `active` flag that the original 0001 did not create. Fresh installs
// get the column from the fixed 0001; this migration is a guarded no-op
// for them.
$schema = Capsule::schema();

if (!$schema->hasTable('slider_images')) {
    return;
}

if (!$schema->hasColumn('slider_images', 'active')) {
    $schema->table('slider_images', function ($t) {
        $t->boolean('active')->default(true);
    });
}
