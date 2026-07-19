<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

// From create-tables.php
if (!$schema->hasTable('categories')) {
    $schema->create('categories', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('slug')->unique();
        $t->integer('parent_id')->nullable();
    });
}

if (!$schema->hasTable('tags')) {
    $schema->create('tags', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('slug')->unique();
    });
}

if (!$schema->hasTable('menus')) {
    $schema->create('menus', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('location')->nullable();
        $t->integer('sort_order')->default(0);
    });
}

if (!$schema->hasTable('users')) {
    $schema->create('users', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('email')->unique();
        $t->string('password');
        $t->timestamps();
    });
}

if (!$schema->hasTable('pages')) {
    $schema->create('pages', function ($t) {
        $t->increments('id');
        $t->integer('parent_id')->nullable();
        $t->string('type')->default('page');
        $t->string('title');
        $t->string('slug')->unique();
        $t->text('content')->nullable();
        $t->text('seo_title')->nullable();
        $t->text('seo_description')->nullable();
        $t->text('seo_keywords')->nullable();
        $t->text('seo_image')->nullable();
        $t->text('meta_title')->nullable();
        $t->text('meta_description')->nullable();
        $t->text('meta_keywords')->nullable();
        $t->text('og_title')->nullable();
        $t->text('og_description')->nullable();
        $t->text('og_image')->nullable();
        $t->text('canonical_url')->nullable();
        $t->string('robots')->nullable();
        $t->string('twitter_card')->nullable();
        $t->string('twitter_site')->nullable();
        $t->integer('noindex')->default(0);
        $t->text('content_json')->nullable();
        $t->string('layout')->default('default');
        $t->string('status')->default('published');
        $t->text('featured_image')->nullable();
        $t->timestamps();
    });
}

if (!$schema->hasTable('content_types')) {
    $schema->create('content_types', function ($t) {
        $t->increments('id');
        $t->string('name');
        $t->string('slug')->unique();
        $t->text('description')->nullable();
        $t->string('icon')->nullable();
        $t->integer('has_categories')->default(1);
        $t->integer('has_tags')->default(1);
        $t->integer('is_system')->default(0);
        $t->timestamps();
    });
}

if (!$schema->hasTable('menu_items')) {
    $schema->create('menu_items', function ($t) {
        $t->increments('id');
        $t->integer('menu_id')->nullable();
        $t->integer('parent_id')->nullable();
        $t->string('title')->nullable();
        $t->string('url')->nullable();
        $t->integer('page_id')->nullable();
        $t->integer('sort_order')->default(0);
        $t->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
        $t->foreign('page_id')->references('id')->on('pages')->onDelete('set null');
    });
}

if (!$schema->hasTable('page_categories')) {
    $schema->create('page_categories', function ($t) {
        $t->integer('page_id');
        $t->integer('category_id');
        $t->primary(['page_id', 'category_id']);
        $t->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
        $t->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
    });
}

if (!$schema->hasTable('page_tags')) {
    $schema->create('page_tags', function ($t) {
        $t->integer('page_id');
        $t->integer('tag_id');
        $t->primary(['page_id', 'tag_id']);
        $t->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
        $t->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
    });
}

if (!$schema->hasTable('templates')) {
    $schema->create('templates', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->text('content_json')->nullable();
        $t->timestamp('created_at')->useCurrent();
    });
}

if (!$schema->hasTable('roles')) {
    $schema->create('roles', function ($t) {
        $t->increments('id');
        $t->string('name');
    });
}

if (!$schema->hasTable('permissions')) {
    $schema->create('permissions', function ($t) {
        $t->increments('id');
        $t->string('name');
    });
}

if (!$schema->hasTable('role_permissions')) {
    $schema->create('role_permissions', function ($t) {
        $t->integer('role_id');
        $t->integer('permission_id');
        $t->primary(['role_id', 'permission_id']);
        $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        $t->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
    });
}

if (!$schema->hasTable('user_roles')) {
    $schema->create('user_roles', function ($t) {
        $t->integer('user_id');
        $t->integer('role_id');
        $t->primary(['user_id', 'role_id']);
        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
    });
}

if (!$schema->hasTable('settings')) {
    $schema->create('settings', function ($t) {
        $t->string('key')->primary();
        $t->text('value')->nullable();
    });
}

if (!$schema->hasTable('media')) {
    $schema->create('media', function ($t) {
        $t->increments('id');
        $t->integer('page_id')->nullable();
        $t->string('filename')->nullable();
        $t->text('path')->nullable();
        $t->string('mime')->nullable();
        $t->integer('size')->nullable();
        $t->dateTime('uploaded_at')->nullable();
        $t->foreign('page_id')->references('id')->on('pages')->onDelete('set null');
    });
}

if (!$schema->hasTable('redirects')) {
    $schema->create('redirects', function ($t) {
        $t->increments('id');
        $t->text('old_url')->nullable();
        $t->text('new_url')->nullable();
        $t->integer('type')->default(301);
    });
}

if (!$schema->hasTable('logs')) {
    $schema->create('logs', function ($t) {
        $t->increments('id');
        $t->integer('user_id')->nullable();
        $t->text('action')->nullable();
        $t->dateTime('created_at')->nullable();
        $t->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    });
}

if (!$schema->hasTable('slider_images')) {
    $schema->create('slider_images', function ($t) {
        $t->increments('id');
        $t->string('title')->nullable();
        $t->text('description')->nullable();
        $t->string('image_path');
        $t->integer('sort_order')->default(0);
        $t->string('link')->nullable();
        $t->timestamps();
    });
}

if (!$schema->hasTable('contact_messages')) {
    $schema->create('contact_messages', function ($t) {
        $t->increments('id');
        $t->string('name');
        $t->string('email');
        $t->string('subject')->nullable();
        $t->text('message');
        $t->string('status')->default('unread');
        $t->timestamps();
    });
}

if (!$schema->hasTable('global_blocks')) {
    $schema->create('global_blocks', function($t) {
        $t->increments('id');
        $t->string('name');
        $t->string('slug')->unique();
        $t->string('title')->nullable();
        $t->boolean('show_title')->default(true);
        $t->text('content')->nullable();
        $t->timestamps();
    });
}

if (!$schema->hasTable('block_placements')) {
    $schema->create('block_placements', function($t) {
        $t->increments('id');
        $t->integer('global_block_id');
        $t->string('location');
        $t->integer('sort_order')->default(0);
        $t->boolean('active')->default(true);
        $t->timestamps();
    });
}

if (!$schema->hasTable('content_type_fields')) {
    $schema->create('content_type_fields', function ($t) {
        $t->increments('id');
        $t->integer('content_type_id');
        $t->string('name');
        $t->string('slug');
        $t->string('type')->default('text'); // text, textarea, image, date, boolean
        $t->text('options')->nullable(); // For select/radio if needed
        $t->integer('sort_order')->default(0);
        $t->timestamps();
        $t->foreign('content_type_id')->references('id')->on('content_types')->onDelete('cascade');
    });
}

if (!$schema->hasTable('plugins')) {
    $schema->create('plugins', function($t) {
        $t->increments('id');
        $t->string('slug')->unique();
        $t->string('name');
        $t->string('version')->default('1.0.0');
        $t->integer('active')->default(0);
        $t->dateTime('installed_at')->nullable();
        $t->timestamps();
    });
}

if (!$schema->hasTable('page_views')) {
    $schema->create('page_views', function($t) {
        $t->increments('id');
        $t->integer('page_id');
        $t->string('ip_address')->nullable();
        $t->string('user_agent')->nullable();
        $t->string('session_id')->nullable();
        $t->date('view_date');
        $t->timestamps();
        $t->index(['page_id', 'view_date']);
    });
}

// Default Content Types
$now = date('Y-m-d H:i:s');
Capsule::table('content_types')->updateOrInsert(['slug' => 'page'], ['name' => 'Page', 'is_system' => 1, 'created_at' => $now]);
Capsule::table('content_types')->updateOrInsert(['slug' => 'feature'], ['name' => 'Feature', 'is_system' => 0, 'created_at' => $now]);
Capsule::table('content_types')->updateOrInsert(['slug' => 'product'], ['name' => 'Product', 'is_system' => 0, 'created_at' => $now]);

// FTS
try {
    Capsule::connection()->statement("CREATE VIRTUAL TABLE IF NOT EXISTS pages_fts USING fts5(title, content, slug, content='pages', content_rowid='id')");
} catch (\Throwable $e) {}

// Seed RBAC Roles
Capsule::table('roles')->updateOrInsert(['name' => 'Administrator']);
Capsule::table('roles')->updateOrInsert(['name' => 'Editor']);

// Seed Permissions
$permissions = [
    'manage_settings', 'manage_pages', 'manage_taxonomies', 'manage_menus',
    'manage_media', 'manage_content_types', 'manage_blocks', 'manage_templates',
    'manage_themes', 'manage_plugins', 'manage_users'
];

foreach ($permissions as $perm) {
    Capsule::table('permissions')->updateOrInsert(['name' => $perm]);
}

// Assign all permissions to Administrator
$adminRole = Capsule::table('roles')->where('name', 'Administrator')->first();
if ($adminRole) {
    $permIds = Capsule::table('permissions')->pluck('id')->toArray();
    foreach ($permIds as $pid) {
        Capsule::table('role_permissions')->updateOrInsert([
            'role_id' => $adminRole->id,
            'permission_id' => $pid
        ]);
    }
    
    // Auto-assign Administrator role to user #1 to prevent lockout on existing sites
    $firstUser = Capsule::table('users')->orderBy('id')->first();
    if ($firstUser) {
        Capsule::table('user_roles')->updateOrInsert([
            'user_id' => $firstUser->id,
            'role_id' => $adminRole->id
        ]);
    }
}

// Settings
Capsule::table('settings')->updateOrInsert(['key' => 'site_name'], ['value' => 'PankhCMS']);
Capsule::table('settings')->updateOrInsert(['key' => 'active_theme'], ['value' => 'default']);
