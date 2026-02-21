        // ---------- Product Galleries ----------
        if (!$schema->hasTable('product_galleries')) {
            $schema->create('product_galleries', function ($t) {
                $t->increments('id');
                $t->string('title');
                $t->string('image_path');
                $t->string('caption')->nullable();
                $t->integer('sort_order')->default(0);
                $t->boolean('active')->default(1);
                $t->timestamps();
            });
        }
    // ---------- Content Type Fields ----------
    if (!$schema->hasTable('content_type_fields')) {
        $schema->create('content_type_fields', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('content_type_id');
            $t->string('name', 100);
            $t->string('label', 255);
            $t->string('type', 50)->default('text');
            $t->text('options')->nullable(); // For select, radio, etc (JSON)
            $t->tinyInteger('required')->default(0);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->foreign('content_type_id')->references('id')->on('content_types')->onDelete('cascade');
        });
    }
<?php

$root = dirname(__DIR__, 2);

if (file_exists(__DIR__ . '/lock') || file_exists($root . '/.env')) {
    http_response_code(403);
    die('Installer is locked.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installDemo = isset($_POST['install_demo']) && $_POST['install_demo'] === '1';

    $driver = $_POST['db_driver'] ?? 'sqlite';
    $env = "\nAPP_URL={$_POST['app_url']}\nDB_DRIVER={$driver}\nDB_CONNECTION={$driver}\nACTIVE_THEME=default\n";

    if ($driver === 'mysql') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');

        if ($dbHost === 'localhost') {
            $dbHost = '127.0.0.1';
        }

        $env .= "DB_HOST={$dbHost}\nDB_PORT={$dbPort}\nDB_DATABASE={$_POST['db_database']}\nDB_USERNAME={$_POST['db_username']}\nDB_PASSWORD={$_POST['db_password']}\n";
    } else {
        $absoluteSqlite = $root . '/database/database.sqlite';
        $env .= "DB_DATABASE={$absoluteSqlite}\n";
    }

    file_put_contents($root . '/.env', trim($env));

    if ($driver === 'sqlite') {
        if (!file_exists($root . '/database')) {
            mkdir($root . '/database', 0777, true);
        }
        touch($root . '/database/database.sqlite');
    }

    require $root . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->load();

    require $root . '/app/database.php';

    // SQLite only: PRAGMA foreign_keys = ON
    if ($driver === 'sqlite') {
        \Illuminate\Database\Capsule\Manager::connection()->statement('PRAGMA foreign_keys = ON');
    }

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

    // ---------- Menu Items ----------
    if (!$schema->hasTable('menu_items')) {
        $schema->create('menu_items', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('menu_id')->nullable();
            $t->unsignedInteger('parent_id')->nullable();
            $t->string('title')->nullable();
            $t->string('url')->nullable();
            $t->unsignedInteger('page_id')->nullable();
            $t->integer('sort_order')->default(0);

            $t->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $t->foreign('page_id')->references('id')->on('pages')->onDelete('set null');
        });
    }

    // ---------- Page Categories Pivot ----------
    if (!$schema->hasTable('page_categories')) {
        $schema->create('page_categories', function ($t) {
            $t->unsignedInteger('page_id');
            $t->unsignedInteger('category_id');
            $t->primary(['page_id', 'category_id']);

            $t->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $t->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    // ---------- Page Tags Pivot ----------
    if (!$schema->hasTable('page_tags')) {
        $schema->create('page_tags', function ($t) {
            $t->unsignedInteger('page_id');
            $t->unsignedInteger('tag_id');
            $t->primary(['page_id', 'tag_id']);

            $t->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $t->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    // ---------- Templates ----------
    if (!$schema->hasTable('templates')) {
        $schema->create('templates', function ($t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->text('content_json')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    // ---------- Roles ----------
    if (!$schema->hasTable('roles')) {
        $schema->create('roles', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
    }

    // ---------- Permissions ----------
    if (!$schema->hasTable('permissions')) {
        $schema->create('permissions', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
    }

    // ---------- Role Permissions Pivot ----------
    if (!$schema->hasTable('role_permissions')) {
        $schema->create('role_permissions', function ($t) {
            $t->unsignedInteger('role_id');
            $t->unsignedInteger('permission_id');

            $t->primary(['role_id', 'permission_id']);
            $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $t->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    // ---------- User Roles Pivot ----------
    if (!$schema->hasTable('user_roles')) {
        $schema->create('user_roles', function ($t) {
            $t->unsignedInteger('user_id');
            $t->unsignedInteger('role_id');

            $t->primary(['user_id', 'role_id']);
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    // ---------- Settings ----------
    if (!$schema->hasTable('settings')) {
        $schema->create('settings', function ($t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
    }

    // ---------- Media ----------
    if (!$schema->hasTable('media')) {
        $schema->create('media', function ($t) {
            $t->increments('id');
            $t->string('filename');
            $t->string('original_name')->nullable();
            $t->string('mime_type')->nullable();
            $t->integer('size')->nullable();
            $t->string('url');
            $t->integer('user_id')->nullable();
            $t->string('alt')->nullable();
            $t->string('title')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });
    }

    // ---------- Slider Images ----------
    if (!$schema->hasTable('slider_images')) {
        $schema->create('slider_images', function ($t) {
            $t->increments('id');
            $t->string('image_path');
            $t->string('caption')->nullable();
            $t->string('link')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('active')->default(1);
            $t->timestamps();
        });
    }

    // ---------- Redirects ----------
    if (!$schema->hasTable('redirects')) {
        $schema->create('redirects', function ($t) {
            $t->increments('id');
            $t->text('old_url')->nullable();
            $t->text('new_url')->nullable();
            $t->integer('type')->default(301);
        });
    }

    // ---------- Logs ----------
    if (!$schema->hasTable('logs')) {
        $schema->create('logs', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('user_id')->nullable();
            $t->text('action')->nullable();
            $t->dateTime('created_at')->nullable();

            $t->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    // ---------- Useful Indexes ----------
    \Illuminate\Database\Capsule\Manager::connection()->statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug)');
    \Illuminate\Database\Capsule\Manager::connection()->statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug)');
    \Illuminate\Database\Capsule\Manager::connection()->statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_tags_slug ON tags(slug)');

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

    // ---------- Default Settings ----------
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'site_name'], ['value' => 'PankhCMS']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'site_tagline'], ['value' => 'A lightweight CMS']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'active_theme'], ['value' => 'default']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'breadcrumbs_enabled'], ['value' => '1']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'breadcrumbs_type'], ['value' => 'auto']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'breadcrumbs_show_home'], ['value' => '1']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'breadcrumbs_home_label'], ['value' => 'Home']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'breadcrumbs_separator'], ['value' => '/']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'breadcrumbs_schema'], ['value' => '1']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'homepage_id'], ['value' => '']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'posts_per_page'], ['value' => '10']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'timezone'], ['value' => 'UTC']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'logo_path'], ['value' => '']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'site_url'], ['value' => '']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'favicon_path'], ['value' => '']);
    \Illuminate\Database\Capsule\Manager::table('settings')->updateOrInsert(['key' => 'show_theme_credit'], ['value' => '1']);

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

<h3>Database Type</h3>
<select name="db_driver" id="db_driver" onchange="toggleDbFields()">
  <option value="sqlite" selected>SQLite (default)</option>
  <option value="mysql">MySQL</option>
</select>

<div id="mysql-fields" style="display:none;">
  <label>MySQL Host</label>
    <input name="db_host" value="127.0.0.1">
    <label>MySQL Port</label>
    <input name="db_port" value="3306">
  <label>MySQL Database</label>
  <input name="db_database">
  <label>MySQL Username</label>
  <input name="db_username">
  <label>MySQL Password</label>
  <input type="password" name="db_password">
</div>

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
<script>
function toggleDbFields() {
  var driver = document.getElementById('db_driver').value;
  document.getElementById('mysql-fields').style.display = (driver === 'mysql') ? '' : 'none';
}
document.getElementById('db_driver').addEventListener('change', toggleDbFields);
window.onload = toggleDbFields;
</script>
</div>
</body>
</html>
