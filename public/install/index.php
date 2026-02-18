<?php

$root = dirname(__DIR__, 2);

if (file_exists(__DIR__ . '/lock') || file_exists($root . '/.env')) {
    http_response_code(403);
    die('Installer is locked.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installDemo = isset($_POST['install_demo']) && $_POST['install_demo'] === '1';

    $env = "
APP_URL={$_POST['app_url']}
DB_DRIVER=sqlite
DB_DATABASE=database/database.sqlite
ACTIVE_THEME=default
";

    file_put_contents($root . '/.env', trim($env));

    if (!file_exists($root . '/database')) {
        mkdir($root . '/database', 0777, true);
    }

    touch($root . '/database/database.sqlite');

    require $root . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->load();

    require $root . '/app/database.php';

    \Illuminate\Database\Capsule\Manager::connection()->statement('PRAGMA foreign_keys = ON');

    $schema = \Illuminate\Database\Capsule\Manager::schema();

    // ---------- Categories ----------
    if (!$schema->hasTable('categories')) {
        $schema->create('categories', function ($t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('slug')->unique();
            $t->integer('parent_id')->nullable();
        });
    }

    // ---------- Tags ----------
    if (!$schema->hasTable('tags')) {
        $schema->create('tags', function ($t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('slug')->unique();
        });
    }

    // ⭐ NEW — CONTENT TYPES TABLE
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

    // ---------- Menus ----------
    if (!$schema->hasTable('menus')) {
        $schema->create('menus', function ($t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('location')->nullable();
            $t->integer('sort_order')->default(0);
        });
    }

    // ---------- Users ----------
    if (!$schema->hasTable('users')) {
        $schema->create('users', function ($t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('email')->unique();
            $t->string('password');
            $t->timestamps();
        });
    }

    // ---------- Pages ----------
    if (!$schema->hasTable('pages')) {
        $schema->create('pages', function ($t) {
            $t->increments('id');
            $t->integer('parent_id')->nullable();

            // ⭐ NEW — TYPE FIELD
            $t->string('type')->default('page');

            $t->string('title');
            $t->string('slug')->unique();
            $t->text('content')->nullable();

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

    // ⭐ SEED DEFAULT CONTENT TYPES
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

    // ⭐ Example admin user
    \Illuminate\Database\Capsule\Manager::table('users')->updateOrInsert(
        ['email' => $_POST['admin_email']],
        [
            'password' => password_hash($_POST['admin_password'], PASSWORD_DEFAULT),
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    file_put_contents(__DIR__ . '/lock', 'installed');

    header('Location: /install/complete.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>CMS Setup</title>
<style>
body{font-family:Arial;background:#f5f5f5}
.box{max-width:500px;margin:60px auto;background:#fff;padding:25px;border-radius:8px}
input{width:100%;padding:8px;margin:8px 0}
button{padding:10px 15px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer}
button:hover{background:#0056b3}
</style>
</head>
<body>
<div class="box">
<h2>🚀 CMS Setup Wizard</h2>
<form method="POST">
<label>App URL</label>
<input name="app_url" value="http://<?php echo $_SERVER['HTTP_HOST']; ?>">

<h3>Admin Account</h3>
<label>Email</label>
<input name="admin_email" required>

<label>Password</label>
<input type="password" name="admin_password" required>

<label style="display:flex;align-items:center;gap:8px;margin-top:10px;">
<input type="checkbox" name="install_demo" value="1" checked>
Install demo content
</label>

<button>Install CMS</button>
</form>
</div>
</body>
</html>
