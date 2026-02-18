<?php

require __DIR__ . '/vendor/autoload.php';

use App\Core\Bootstrap;
use App\Models\Page;
use App\Models\User;

// Initialize the application to get the database connection
Bootstrap::init();

// Demo pages
\Illuminate\Database\Capsule\Manager::table('pages')->insert([
    [
        'type' => 'page',
        'title' => 'Home',
        'slug' => 'home',
        'content' => '<h1>Welcome to PankhCMS!</h1>',
        'layout' => 'default',
        'status' => 'published',
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'type' => 'page',
        'title' => 'About',
        'slug' => 'about',
        'content' => '<h2>About PankhCMS</h2>',
        'layout' => 'default',
        'status' => 'published',
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'type' => 'feature',
        'title' => 'Fast & Lightweight',
        'slug' => 'fast-lightweight',
        'content' => '<p>PankhCMS is optimized for speed and simplicity.</p>',
        'layout' => 'default',
        'status' => 'published',
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'type' => 'product',
        'title' => 'PankhCMS Pro',
        'slug' => 'pankhcms-pro',
        'content' => '<p>Upgrade to PankhCMS Pro for advanced features.</p>',
        'layout' => 'default',
        'status' => 'published',
        'created_at' => $now,
        'updated_at' => $now,
    ],
]);
        // Seed default content types
        \Illuminate\Database\Capsule\Manager::table('content_types')->updateOrInsert(
            ['slug' => 'page'],
            [
                'name' => 'Page',
                'description' => 'Standard website page',
                'icon' => 'file',
                'has_categories' => 1,
                'has_tags' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        \Illuminate\Database\Capsule\Manager::table('content_types')->updateOrInsert(
            ['slug' => 'feature'],
            [
                'name' => 'Feature',
                'description' => 'Product or service feature',
                'icon' => 'star',
                'has_categories' => 1,
                'has_tags' => 1,
                'is_system' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        \Illuminate\Database\Capsule\Manager::table('content_types')->updateOrInsert(
            ['slug' => 'product'],
            [
                'name' => 'Product',
                'description' => 'Sellable product',
                'icon' => 'shopping-cart',
                'has_categories' => 1,
                'has_tags' => 1,
                'is_system' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    echo "✅ Pages table seeded\n";
// End of seeding block
