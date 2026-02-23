// ---------- Content Types ----------
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
    echo "✅ Content types table created\n";
// End of content_types seeding block
<?php

require __DIR__ . "/../../vendor/autoload.php";

use App\Core\Bootstrap;
use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize the application to get the database connection
Bootstrap::init();

Capsule::connection()->statement('PRAGMA foreign_keys = ON');

$schema = Capsule::schema();

if (!$schema->hasTable('categories')) {
    $schema->create('categories', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('slug')->unique();
        $t->integer('parent_id')->nullable();
    });
    echo "✅ Categories table created\n";
// End of content_types seeding block

if (!$schema->hasTable('tags')) {
    $schema->create('tags', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('slug')->unique();
    });
    echo "✅ Tags table created\n";
}

if (!$schema->hasTable('menus')) {
    $schema->create('menus', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('location')->nullable();
        $t->integer('sort_order')->default(0);
    });
    echo "✅ Menus table created\n";
}

if (!$schema->hasTable('users')) {
    $schema->create('users', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('email')->unique();
        $t->string('password');
        $t->timestamps();
    });
    echo "✅ Users table created\n";
}

if (!$schema->hasTable('pages')) {
    $schema->create('pages', function ($t) {
        $t->increments('id');
        $t->integer('parent_id')->nullable();
        $t->string('type')->default('page'); // ⭐ NEW
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
        // New SEO fields for pages
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
    echo "✅ Pages table created\n";
}
// ---------- Default Content Types ----------
$now = date('Y-m-d H:i:s');
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
}

try {
    Capsule::connection()->statement("CREATE VIRTUAL TABLE IF NOT EXISTS pages_fts USING fts5(title, content, slug, content='pages', content_rowid='id')");
    Capsule::connection()->statement("INSERT INTO pages_fts(rowid, title, content, slug) SELECT id, title, content, slug FROM pages WHERE id NOT IN (SELECT rowid FROM pages_fts)");
    Capsule::connection()->statement("CREATE TRIGGER IF NOT EXISTS pages_ai AFTER INSERT ON pages BEGIN INSERT INTO pages_fts(rowid, title, content, slug) VALUES (new.id, new.title, new.content, new.slug); END;");
    Capsule::connection()->statement("CREATE TRIGGER IF NOT EXISTS pages_ad AFTER DELETE ON pages BEGIN DELETE FROM pages_fts WHERE rowid = old.id; END;");
    Capsule::connection()->statement("CREATE TRIGGER IF NOT EXISTS pages_au AFTER UPDATE ON pages BEGIN UPDATE pages_fts SET title = new.title, content = new.content, slug = new.slug WHERE rowid = new.id; END;");
    echo "✅ Pages FTS index ready\n";
} catch (\Throwable $e) {
    echo "⚠️ FTS5 setup skipped: {$e->getMessage()}\n";
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
    echo "✅ Menu items table created\n";
}

if (!$schema->hasTable('page_categories')) {
    $schema->create('page_categories', function ($t) {
        $t->integer('page_id');
        $t->integer('category_id');
        $t->primary(['page_id', 'category_id']);

        $t->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
        $t->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
    });
    echo "✅ Page categories pivot created\n";
}

if (!$schema->hasTable('page_tags')) {
    $schema->create('page_tags', function ($t) {
        $t->integer('page_id');
        $t->integer('tag_id');
        $t->primary(['page_id', 'tag_id']);

        $t->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
        $t->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
    });
    echo "✅ Page tags pivot created\n";
}

if (!$schema->hasTable('templates')) {
    $schema->create('templates', function ($t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->text('content_json')->nullable();
        $t->timestamp('created_at')->useCurrent();
    });
    echo "✅ Templates table created\n";
}

if (!$schema->hasTable('roles')) {
    $schema->create('roles', function ($t) {
        $t->increments('id');
        $t->string('name');
    });
    echo "✅ Roles table created\n";
}

if (!$schema->hasTable('permissions')) {
    $schema->create('permissions', function ($t) {
        $t->increments('id');
        $t->string('name');
    });
    echo "✅ Permissions table created\n";
}

if (!$schema->hasTable('role_permissions')) {
    $schema->create('role_permissions', function ($t) {
        $t->integer('role_id');
        $t->integer('permission_id');

        $t->primary(['role_id', 'permission_id']);
        $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        $t->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
    });
    echo "✅ Role permissions pivot created\n";
}

if (!$schema->hasTable('user_roles')) {
    $schema->create('user_roles', function ($t) {
        $t->integer('user_id');
        $t->integer('role_id');

        $t->primary(['user_id', 'role_id']);
        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
    });
    echo "✅ User roles pivot created\n";
}

if (!$schema->hasTable('settings')) {
    $schema->create('settings', function ($t) {
        $t->string('key')->primary();
        $t->text('value')->nullable();
    });
    echo "✅ Settings table created\n";
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
    echo "✅ Media table created\n";
}

if (!$schema->hasTable('redirects')) {
    $schema->create('redirects', function ($t) {
        $t->increments('id');
        $t->text('old_url')->nullable();
        $t->text('new_url')->nullable();
        $t->integer('type')->default(301);
    });
    echo "✅ Redirects table created\n";
}

if (!$schema->hasTable('logs')) {
    $schema->create('logs', function ($t) {
        $t->increments('id');
        $t->integer('user_id')->nullable();
        $t->text('action')->nullable();
        $t->dateTime('created_at')->nullable();

        $t->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    });
    echo "✅ Logs table created\n";
}

Capsule::connection()->statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug)');
Capsule::connection()->statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug)');
Capsule::connection()->statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_tags_slug ON tags(slug)');

Capsule::table('settings')->updateOrInsert(['key' => 'site_name'], ['value' => 'PankhCMS']);
Capsule::table('settings')->updateOrInsert(['key' => 'site_tagline'], ['value' => 'A lightweight CMS']);
Capsule::table('settings')->updateOrInsert(['key' => 'site_title'], ['value' => 'PankhCMS']);
Capsule::table('settings')->updateOrInsert(['key' => 'tagline'], ['value' => 'Lightweight PHP CMS']);
Capsule::table('settings')->updateOrInsert(['key' => 'site_url'], ['value' => env('APP_URL', '')]);
Capsule::table('settings')->updateOrInsert(['key' => 'default_meta_description'], ['value' => 'Modern CMS for fast websites']);
Capsule::table('settings')->updateOrInsert(['key' => 'default_meta_keywords'], ['value' => 'cms, php, website']);
Capsule::table('settings')->updateOrInsert(['key' => 'favicon'], ['value' => '/uploads/favicon.ico']);
Capsule::table('settings')->updateOrInsert(['key' => 'og_image'], ['value' => '/uploads/og.jpg']);
Capsule::table('settings')->updateOrInsert(['key' => 'active_theme'], ['value' => 'default']);
// Default Breadcrumb Settings
Capsule::table('settings')->updateOrInsert(['key' => 'breadcrumbs_enabled'], ['value' => '1']);
Capsule::table('settings')->updateOrInsert(['key' => 'breadcrumbs_type'], ['value' => 'auto']);
Capsule::table('settings')->updateOrInsert(['key' => 'breadcrumbs_show_home'], ['value' => '1']);
Capsule::table('settings')->updateOrInsert(['key' => 'breadcrumbs_home_label'], ['value' => 'Home']);
Capsule::table('settings')->updateOrInsert(['key' => 'breadcrumbs_separator'], ['value' => '/']);
Capsule::table('settings')->updateOrInsert(['key' => 'breadcrumbs_schema'], ['value' => '1']);
Capsule::table('settings')->updateOrInsert(['key' => 'homepage_id'], ['value' => '']);
Capsule::table('settings')->updateOrInsert(['key' => 'posts_per_page'], ['value' => '10']);
Capsule::table('settings')->updateOrInsert(['key' => 'timezone'], ['value' => 'UTC']);
Capsule::table('settings')->updateOrInsert(['key' => 'logo_path'], ['value' => '']);

echo "✅ Schema and defaults are up to date\n";
