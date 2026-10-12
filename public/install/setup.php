<?php
use Illuminate\Database\Capsule\Manager as Capsule;

// Log everything, but never print errors to the browser: the installer
// renders its own friendly failure page in the catch block below.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// --- Secure session-based auto-login installer ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root = realpath(__DIR__ . '/../..');
$lockFile = __DIR__ . '/lock';
$envFile  = $root . '/.env';

if (file_exists($lockFile) || file_exists($root . '/.env')) {
    die('Installer is locked.');
}

function clean($v) {
    return trim(htmlspecialchars($v ?? '', ENT_QUOTES));
}
function envEscape($v) {
    // The real escaping lives in app/Helpers/functions.php so it can be
    // unit tested; see env_escape().
    return env_escape($v);
}
function normalizeUsername($value) {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9._-]+/', '_', $value) ?? '';
    $value = trim($value, '._-');
    return $value !== '' ? $value : 'user';
}
function checkWritable($path) {
    return is_writable($path);
}
function detectProtocol() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    return $isHttps ? 'https' : 'http';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dbDriver   = $_POST['db_driver']   ?? 'sqlite';
        $appUrl     = trim($_POST['app_url'] ?? '');
        $adminUsername = normalizeUsername($_POST['admin_username'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass  = $_POST['admin_password'] ?? '';

        if (!$adminUsername || !$adminEmail || !$adminPass) {
            die('Admin username, email and password are required.');
        }

        // The composer autoloader also loads app/Helpers/functions.php,
        // which provides password_policy_errors().
        require_once $root . '/vendor/autoload.php';

        $passwordErrors = password_policy_errors($adminPass);
        if (!empty($passwordErrors)) {
            die('Password does not meet the policy: ' . implode(' ', $passwordErrors));
        }

        // ── 1. Build .env content ──────────────────────────────────────
        // Admin credentials are never written to .env; the admin user is
        // created directly in the database during installation.
        if ($dbDriver === 'mysql') {
            $dbHost     = trim($_POST['db_host']     ?? '127.0.0.1');
            $dbPort     = trim($_POST['db_port']     ?? '3306');
            $dbDatabase = trim($_POST['db_database'] ?? '');
            $dbUsername = trim($_POST['db_username'] ?? '');
            $dbPassword = trim($_POST['db_password'] ?? '');

            $envContent = "APP_NAME=\"PankhCMS\"\n"
                . "APP_ENV=production\n"
                . "APP_DEBUG=false\n"
                . "APP_URL=" . envEscape($appUrl) . "\n"
                . "\n"
                . "DB_CONNECTION=mysql\n"
                . "DB_HOST=" . envEscape($dbHost) . "\n"
                . "DB_PORT=" . envEscape($dbPort) . "\n"
                . "DB_DATABASE=" . envEscape($dbDatabase) . "\n"
                . "DB_USERNAME=" . envEscape($dbUsername) . "\n"
                . "DB_PASSWORD=" . envEscape($dbPassword) . "\n";
        } else {
            $envContent = "APP_NAME=\"PankhCMS\"\n"
                . "APP_ENV=production\n"
                . "APP_DEBUG=false\n"
                . "APP_URL=" . envEscape($appUrl) . "\n"
                . "\n"
                . "DB_CONNECTION=sqlite\n";
        }

        // ── 2. Write .env ──────────────────────────────────────────────
        if (file_put_contents($envFile, $envContent) === false) {
            die('Failed to write .env file. Check folder permissions.');
        }

        // Populate $_ENV so env() helpers work
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"");
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }

        $capsule = new Capsule();

        if ($dbDriver === 'mysql') {
            $capsule->addConnection([
                'driver'    => 'mysql',
                'host'      => $dbHost,
                'port'      => $dbPort,
                'database'  => $dbDatabase,
                'username'  => $dbUsername,
                'password'  => $dbPassword,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => true,
                'engine'    => null,
            ]);
        } else {
            // Ensure the database directory exists
            $dbDir = $root . '/database';
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0775, true);
            }
            $dbPath = $dbDir . '/database.sqlite';
            if (!file_exists($dbPath)) {
                touch($dbPath);
            }
            $capsule->addConnection([
                'driver'   => 'sqlite',
                'database' => $dbPath,
                'prefix'   => '',
                'foreign_key_constraints' => true,
            ]);
        }

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        if ($dbDriver === 'sqlite') {
            Capsule::connection()->statement('PRAGMA foreign_keys = ON');
        }

        // ── 4. Run migrations ──────────────────────────────────────────
        // database/migrations is the single source of truth for the
        // schema; the installer runs them via scripts/migrate.php.
        ob_start();
        require $root . '/scripts/migrate.php';
        ob_end_clean();

        $now = date('Y-m-d H:i:s');

        $adminRoleId = Capsule::table('roles')->where('name', 'Administrator')->value('id');

        // ── 7. Seed default settings ──────────────────────────────────
        $appName = 'PankhCMS';
        $settingsDefaults = [
            'site_name'               => $appName,
            'site_title'              => $appName,
            'site_tagline'            => 'A lightweight CMS',
            'header_cta_label'        => 'Get Quote',
            'header_cta_url'          => '/contact-us',
            'hero_intro_text'         => 'Transforming ideas into powerful digital solutions with cutting-edge technology.',
            'hero_primary_cta_label'  => 'Get Started',
            'hero_primary_cta_url'    => '/contact-us',
            'hero_secondary_cta_label'=> 'View Projects',
            'hero_secondary_cta_url'  => '/projects',
            'tagline'                 => 'Lightweight PHP CMS',
            'site_url'                => $appUrl,
            'default_meta_description'=> 'Modern CMS for fast websites',
            'default_meta_keywords'   => 'cms, php, website',
            'favicon'                 => '/uploads/favicon.ico',
            'og_image'                => '/uploads/og.jpg',
            'active_theme'            => 'pankhcmsstarter',
            'breadcrumbs_enabled'     => '1',
            'breadcrumbs_type'        => 'auto',
            'breadcrumbs_show_home'   => '1',
            'breadcrumbs_home_label'  => 'Home',
            'breadcrumbs_separator'   => '/',
            'breadcrumbs_schema'      => '1',
            'homepage_id'             => '',
            'posts_per_page'          => '10',
            'timezone'                => 'UTC',
            'logo_path'               => '',
        ];
        foreach ($settingsDefaults as $key => $value) {
            Capsule::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        // ── 8. Create admin user ──────────────────────────────────────
        $existingUser = Capsule::table('users')->where('email', $adminEmail)->first();
        if (!$existingUser) {
            $userId = Capsule::table('users')->insertGetId([
                'username'   => $adminUsername,
                'email'      => $adminEmail,
                'password'   => password_hash($adminPass, PASSWORD_DEFAULT),
                'name'       => 'Administrator',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $userId = $existingUser->id;
        }

        // ── 9. Assign admin role ──────────────────────────────────────
        Capsule::table('user_roles')->insertOrIgnore([
            'user_id' => $userId,
            'role_id' => $adminRoleId,
        ]);

        // ── 9b. Seed demo data ────────────────────────────────────────
        $demoType = $_POST['demo_type'] ?? 'blank';

        // ── Demo: DigiWings Technologies — Corporate IT ───────────────
        $seedCorporateIT = function () use ($now) {
            $pages = [
                ['id' => 1,  'parent_id' => null, 'type' => 'page', 'title' => 'Home',                   'slug' => 'home',                           'content' => '<h1>Welcome to DigiWings Technologies</h1><p>Transforming Ideas Into Powerful Digital Solutions. We deliver cutting-edge web, mobile, cloud and digital solutions for businesses worldwide.</p>', 'meta_title' => 'DigiWings Technologies — IT Solutions Company',         'meta_description' => 'Web, Mobile, Cloud & Digital Solutions Provider',    'status' => 'published', 'featured_image' => '/media/demo/home-hero.jpg'],
                ['id' => 2,  'parent_id' => null, 'type' => 'page', 'title' => 'About Us',               'slug' => 'about-us',                       'content' => '<h1>About DigiWings Technologies</h1><p>DigiWings Technologies is a technology-driven company delivering innovative digital solutions worldwide. Our team of experts is passionate about solving complex business challenges through technology.</p>', 'meta_title' => 'About DigiWings Technologies',                          'meta_description' => 'Learn about our mission, vision and team.',          'status' => 'published', 'featured_image' => '/media/demo/about.jpg'],
                ['id' => 3,  'parent_id' => null, 'type' => 'page', 'title' => 'Services',               'slug' => 'services',                       'content' => '<h1>Our Services</h1><p>Explore our full range of technology services. From web development to mobile apps, cloud solutions and UI/UX design — we have you covered.</p>', 'meta_title' => 'Our Services',                                          'meta_description' => 'Web, mobile, cloud and marketing services.',        'status' => 'published', 'featured_image' => null],
                ['id' => 4,  'parent_id' => 3,   'type' => 'page', 'title' => 'Web Development',         'slug' => 'web-development',                'content' => '<h1>Web Development</h1><p>Custom websites and enterprise web applications built with the latest technologies. We create fast, secure and scalable web solutions tailored to your business needs.</p>', 'meta_title' => 'Web Development Services',                              'meta_description' => 'Professional website development solutions.',        'status' => 'published', 'featured_image' => '/media/demo/web-dev.jpg'],
                ['id' => 5,  'parent_id' => 3,   'type' => 'page', 'title' => 'Mobile App Development', 'slug' => 'mobile-app-development',         'content' => '<h1>Mobile App Development</h1><p>Native and cross-platform mobile apps for Android and iOS. We build high-performance mobile applications that deliver exceptional user experiences.</p>', 'meta_title' => 'Mobile App Development',                                'meta_description' => 'Android and iOS app development services.',         'status' => 'published', 'featured_image' => '/media/demo/mobile.jpg'],
                ['id' => 6,  'parent_id' => 3,   'type' => 'page', 'title' => 'UI/UX Design',           'slug' => 'ui-ux-design',                   'content' => '<h1>UI/UX Design</h1><p>User-centered design solutions that enhance usability and engagement. We create intuitive interfaces that delight users and drive conversions.</p>', 'meta_title' => 'UI UX Design Services',                                 'meta_description' => 'Improve usability and engagement.',                 'status' => 'published', 'featured_image' => '/media/demo/uiux.jpg'],
                ['id' => 7,  'parent_id' => null, 'type' => 'page', 'title' => 'Projects',              'slug' => 'projects',                       'content' => '<h1>Our Projects</h1><p>Explore successful projects delivered by DigiWings Technologies. Our portfolio showcases our expertise across industries and technology stacks.</p>', 'meta_title' => 'Our Projects',                                          'meta_description' => 'Portfolio of completed projects.',                  'status' => 'published', 'featured_image' => '/media/demo/projects.jpg'],
                ['id' => 8,  'parent_id' => null, 'type' => 'page', 'title' => 'Blog',                  'slug' => 'blog',                           'content' => '<h1>Blog</h1><p>Insights, articles, and industry updates from DigiWings Technologies.</p>', 'meta_title' => 'DigiWings Technologies Blog',                           'meta_description' => 'Technology news and tips.',                         'status' => 'published', 'featured_image' => null],
                ['id' => 9,  'parent_id' => null, 'type' => 'page', 'title' => 'Careers',               'slug' => 'careers',                        'content' => '<h1>Careers at DigiWings Technologies</h1><p>Join DigiWings Technologies and build the future with us. We are always looking for talented individuals who share our passion for technology.</p>', 'meta_title' => 'Careers at DigiWings Technologies',                    'meta_description' => 'Explore job opportunities.',                        'status' => 'published', 'featured_image' => '/media/demo/careers.jpg'],
                ['id' => 10, 'parent_id' => null, 'type' => 'page', 'title' => 'Contact Us',            'slug' => 'contact-us',                     'content' => '<h1>Contact DigiWings Technologies</h1><p>Get in touch with us for your next digital project. Our team is ready to help you achieve your business goals.</p>', 'meta_title' => 'Contact DigiWings Technologies',                        'meta_description' => 'Contact details and form.',                         'status' => 'published', 'featured_image' => '/media/demo/contact.jpg'],
                // Blog posts
                ['id' => 11, 'parent_id' => null, 'type' => 'post', 'title' => 'Top Web Development Trends in 2026', 'slug' => 'web-development-trends-2026', 'content' => '<p>Explore the latest technologies shaping the future of web development. From AI-driven interfaces to edge computing, 2026 promises exciting advancements for developers and businesses alike.</p>', 'meta_title' => 'Top Web Development Trends in 2026', 'meta_description' => 'Key trends developers should watch in 2026.', 'status' => 'published', 'featured_image' => '/media/demo/blog1.jpg'],
                ['id' => 12, 'parent_id' => null, 'type' => 'post', 'title' => 'Mobile App vs Web App — Which to Choose?', 'slug' => 'mobile-vs-web-app', 'content' => '<p>A comprehensive comparison to help businesses choose the right platform. We break down the pros and cons of mobile and web applications to guide your decision.</p>', 'meta_title' => 'Mobile App vs Web App', 'meta_description' => 'Choosing between mobile and web applications.', 'status' => 'published', 'featured_image' => '/media/demo/blog2.jpg'],
                ['id' => 13, 'parent_id' => null, 'type' => 'post', 'title' => 'How Cloud Computing Benefits SMEs', 'slug' => 'cloud-benefits-sme', 'content' => '<p>Cloud solutions help small businesses scale efficiently without large capital expenditure. Discover how cloud technology can transform your SME and drive growth.</p>', 'meta_title' => 'Cloud Computing for SMEs', 'meta_description' => 'Why SMEs should adopt cloud technologies.', 'status' => 'published', 'featured_image' => '/media/demo/blog3.jpg'],
                ['id' => 14, 'parent_id' => null, 'type' => 'post', 'title' => 'SEO Basics for Business Owners', 'slug' => 'seo-basics-business', 'content' => '<p>Learn the fundamentals of search engine optimization and improve your website visibility on Google. A practical guide for business owners with no technical background.</p>', 'meta_title' => 'SEO Basics for Business Owners', 'meta_description' => 'Improve your website visibility on Google.', 'status' => 'published', 'featured_image' => '/media/demo/blog4.jpg'],
            ];
            foreach ($pages as $page) {
                Capsule::table('pages')->insertOrIgnore(array_merge($page, ['created_at' => $now, 'updated_at' => $now]));
            }

            // Add 'post' content type for blog posts
            Capsule::table('content_types')->updateOrInsert(
                ['slug' => 'post'],
                ['name' => 'Post', 'description' => 'Blog post', 'icon' => 'file-text', 'has_categories' => 1, 'has_tags' => 1, 'is_system' => 0, 'created_at' => $now, 'updated_at' => $now]
            );

            // Blog categories
            $cats = [
                ['id' => 1, 'name' => 'Web Development',  'slug' => 'web-development',  'type' => 'post'],
                ['id' => 2, 'name' => 'Mobile Apps',       'slug' => 'mobile-apps',       'type' => 'post'],
                ['id' => 3, 'name' => 'Cloud Computing',   'slug' => 'cloud-computing',   'type' => 'post'],
                ['id' => 4, 'name' => 'Digital Marketing', 'slug' => 'digital-marketing', 'type' => 'post'],
            ];
            foreach ($cats as $cat) {
                Capsule::table('categories')->insertOrIgnore($cat);
            }

            // Link blog posts to categories (post page_id → category slug map)
            $postCatMap = [11 => 1, 12 => 2, 13 => 3, 14 => 4];
            foreach ($postCatMap as $pageId => $catId) {
                Capsule::table('page_categories')->insertOrIgnore(['page_id' => $pageId, 'category_id' => $catId]);
            }

            // Testimonials
            $testimonials = [
                ['name' => 'Rajesh Patel', 'company' => 'Manufacturing Firm',  'content' => 'DigiWings Technologies delivered our project on time with excellent quality.', 'status' => 'active'],
                ['name' => 'Sneha Shah',   'company' => 'E-Commerce Startup',  'content' => 'Professional team and outstanding support.',                                    'status' => 'active'],
            ];
            foreach ($testimonials as $t) {
                Capsule::table('testimonials')->insert(array_merge($t, ['created_at' => $now, 'updated_at' => $now]));
            }

            // Update settings for corporate demo
            Capsule::table('settings')->updateOrInsert(['key' => 'site_name'],    ['value' => 'DigiWings Technologies']);
            Capsule::table('settings')->updateOrInsert(['key' => 'site_title'],   ['value' => 'DigiWings Technologies — IT Solutions']);
            Capsule::table('settings')->updateOrInsert(['key' => 'site_tagline'], ['value' => 'Transforming Ideas Into Powerful Digital Solutions']);
        };

        // ── Demo: NovaTech Instruments — Manufacturing ────────────────
        $seedManufacturing = function () use ($now) {
            $pages = [
                ['id' => 101, 'parent_id' => null,  'type' => 'page', 'title' => 'Home',                  'slug' => 'home',                                          'content' => '<h1>NovaTech Instruments Pvt. Ltd.</h1><p>A leading manufacturer of precision industrial instruments and automation solutions. Trusted by industries worldwide for accuracy, reliability and performance.</p>',     'meta_title' => 'NovaTech Instruments — Industrial Equipment Manufacturer', 'meta_description' => 'Precision instruments, automation systems and industrial solutions.', 'status' => 'published', 'featured_image' => '/media/demo/industrial-hero.jpg'],
                ['id' => 102, 'parent_id' => null,  'type' => 'page', 'title' => 'About Us',              'slug' => 'about-us',                                      'content' => '<h1>About NovaTech Instruments</h1><p>Established in 2010, NovaTech Instruments delivers high-performance industrial equipment worldwide. Our commitment to quality and innovation has made us a trusted partner for leading industries.</p>', 'meta_title' => 'About NovaTech Instruments',                               'meta_description' => 'Manufacturer of precision industrial instruments.',  'status' => 'published', 'featured_image' => '/media/demo/factory.jpg'],
                ['id' => 103, 'parent_id' => null,  'type' => 'page', 'title' => 'Products',              'slug' => 'products',                                      'content' => '<h1>Our Products</h1><p>Browse our complete range of industrial instruments and automation solutions designed for demanding environments.</p>',                                                                   'meta_title' => 'Our Products',                                             'meta_description' => 'Industrial instruments catalog.',                    'status' => 'published', 'featured_image' => null],
                ['id' => 104, 'parent_id' => 103,   'type' => 'page', 'title' => 'Pressure Instruments',  'slug' => 'pressure-instruments',                          'content' => '<h1>Pressure Instruments</h1><p>High-accuracy pressure measurement devices for industrial use including gauges, transmitters and switches for all critical applications.</p>',                                   'meta_title' => 'Pressure Instruments',                                     'meta_description' => 'Pressure gauges and transmitters.',                  'status' => 'published', 'featured_image' => '/media/demo/pressure.jpg'],
                ['id' => 105, 'parent_id' => 103,   'type' => 'page', 'title' => 'Temperature Instruments','slug' => 'temperature-instruments',                       'content' => '<h1>Temperature Instruments</h1><p>Reliable temperature monitoring solutions including RTDs, thermocouples and digital controllers for precise industrial temperature management.</p>',                          'meta_title' => 'Temperature Instruments',                                  'meta_description' => 'Temperature sensors and controllers.',               'status' => 'published', 'featured_image' => '/media/demo/temperature.jpg'],
                ['id' => 106, 'parent_id' => 103,   'type' => 'page', 'title' => 'Flow Meters',           'slug' => 'flow-meters',                                   'content' => '<h1>Flow Meters</h1><p>Advanced flow measurement systems for liquids and gases. Our range includes electromagnetic, ultrasonic, and differential pressure flow meters.</p>',                                    'meta_title' => 'Flow Meters',                                              'meta_description' => 'Industrial flow measurement devices.',               'status' => 'published', 'featured_image' => '/media/demo/flow.jpg'],
                ['id' => 201, 'parent_id' => 104,   'type' => 'product', 'title' => 'Digital Pressure Gauge',    'slug' => 'digital-pressure-gauge',               'content' => '<h1>Digital Pressure Gauge</h1><p>High-precision digital gauge suitable for harsh industrial environments. Features stainless steel housing, IP65 protection, and 0.25% accuracy class.</p>',                  'meta_title' => 'Digital Pressure Gauge',                                   'meta_description' => 'Industrial pressure measurement device.',            'status' => 'published', 'featured_image' => '/media/demo/product-pressure.jpg'],
                ['id' => 202, 'parent_id' => 105,   'type' => 'product', 'title' => 'Temperature Transmitter', 'slug' => 'temperature-transmitter',                  'content' => '<h1>Temperature Transmitter</h1><p>Robust transmitter designed for accurate temperature monitoring in industrial processes. Wide range -200°C to +850°C with 4-20mA output.</p>',                              'meta_title' => 'Temperature Transmitter',                                  'meta_description' => 'Industrial temperature monitoring device.',          'status' => 'published', 'featured_image' => '/media/demo/product-temperature.jpg'],
                ['id' => 203, 'parent_id' => 106,   'type' => 'product', 'title' => 'Ultrasonic Flow Meter',    'slug' => 'ultrasonic-flow-meter',                'content' => '<h1>Ultrasonic Flow Meter</h1><p>Non-invasive flow measurement system with high accuracy for clean and slightly contaminated liquids. Easy installation without pipe cutting.</p>',                             'meta_title' => 'Ultrasonic Flow Meter',                                    'meta_description' => 'Advanced liquid flow measurement.',                  'status' => 'published', 'featured_image' => '/media/demo/product-flow.jpg'],
                ['id' => 120, 'parent_id' => null,  'type' => 'page', 'title' => 'Certifications',         'slug' => 'certifications',                                'content' => '<h1>Certifications</h1><p>ISO certified manufacturing processes ensuring global quality standards. Our products comply with international safety and quality regulations.</p>',                                    'meta_title' => 'Certifications',                                           'meta_description' => 'Quality certifications and approvals.',              'status' => 'published', 'featured_image' => '/media/demo/certifications.jpg'],
                ['id' => 121, 'parent_id' => null,  'type' => 'page', 'title' => 'Infrastructure',         'slug' => 'infrastructure',                                'content' => '<h1>Infrastructure</h1><p>State-of-the-art manufacturing facility equipped with modern machinery, advanced testing labs and automated production lines.</p>',                                                      'meta_title' => 'Infrastructure',                                           'meta_description' => 'Manufacturing plant and facilities.',                'status' => 'published', 'featured_image' => '/media/demo/factory2.jpg'],
                ['id' => 122, 'parent_id' => null,  'type' => 'page', 'title' => 'Contact Us',             'slug' => 'contact-us',                                    'content' => '<h1>Contact NovaTech Instruments</h1><p>Contact NovaTech Instruments for product inquiries, technical support, and sales assistance.</p>',                                                                      'meta_title' => 'Contact NovaTech Instruments',                             'meta_description' => 'Contact details and inquiry form.',                  'status' => 'published', 'featured_image' => '/media/demo/contact-industrial.jpg'],
            ];
            foreach ($pages as $page) {
                Capsule::table('pages')->insertOrIgnore(array_merge($page, ['created_at' => $now, 'updated_at' => $now]));
            }

            // Add 'product' content type
            Capsule::table('content_types')->updateOrInsert(
                ['slug' => 'product'],
                ['name' => 'Product', 'description' => 'Sellable product', 'icon' => 'shopping-cart', 'has_categories' => 1, 'has_tags' => 1, 'is_system' => 0, 'created_at' => $now, 'updated_at' => $now]
            );

            // Product gallery images
            $gallery = [
                ['product_id' => 201, 'image_path' => '/media/demo/pressure1.jpg',    'caption' => 'Front View',          'sort_order' => 1],
                ['product_id' => 201, 'image_path' => '/media/demo/pressure2.jpg',    'caption' => 'Side View',           'sort_order' => 2],
                ['product_id' => 201, 'image_path' => '/media/demo/pressure3.jpg',    'caption' => 'Installation View',   'sort_order' => 3],
                ['product_id' => 202, 'image_path' => '/media/demo/temp1.jpg',        'caption' => 'Sensor Unit',         'sort_order' => 1],
                ['product_id' => 202, 'image_path' => '/media/demo/temp2.jpg',        'caption' => 'Mounted View',        'sort_order' => 2],
                ['product_id' => 203, 'image_path' => '/media/demo/flow1.jpg',        'caption' => 'Clamp-On Type',       'sort_order' => 1],
                ['product_id' => 203, 'image_path' => '/media/demo/flow2.jpg',        'caption' => 'Industrial Setup',    'sort_order' => 2],
            ];
            foreach ($gallery as $img) {
                Capsule::table('product_gallery')->insert($img);
            }

            // Update settings for manufacturing demo
            Capsule::table('settings')->updateOrInsert(['key' => 'site_name'],    ['value' => 'NovaTech Instruments']);
            Capsule::table('settings')->updateOrInsert(['key' => 'site_title'],   ['value' => 'NovaTech Instruments Pvt. Ltd.']);
            Capsule::table('settings')->updateOrInsert(['key' => 'site_tagline'], ['value' => 'Precision Industrial Instruments & Automation Solutions']);
        };

        if ($demoType === 'corporate_it') {
            $seedCorporateIT();
        } elseif ($demoType === 'manufacturing') {
            $seedManufacturing();
        }
        // 'blank' — nothing extra to seed

        // ── 10. Auto-login the new admin ──────────────────────────────
        $_SESSION['auth'] = [
            'id'    => $userId,
            'email' => $adminEmail,
            'role'  => 'admin',
        ];

        // ── 11. Create lock file ──────────────────────────────────────
        file_put_contents($lockFile, date('Y-m-d H:i:s'));

        // ── 12. Redirect to completion page ──────────────────────────
        header('Location: /install/complete.php');
        exit;

    } catch (Throwable $e) {
        // Clean up .env if it was written but something went wrong
        if (file_exists($envFile)) {
            @unlink($envFile);
        }

        echo '<!DOCTYPE html><html><head><title>Installation Error</title>'
            . '<style>body{font-family:Arial;background:#f5f5f5}.box{max-width:600px;margin:60px auto;background:#fff;padding:25px;border-radius:8px}'
            . 'pre{background:#f8d7da;padding:15px;border-radius:4px;white-space:pre-wrap;word-break:break-all}'
            . 'a{color:#007bff}</style></head><body><div class="box">'
            . '<h2 style="color:#dc3545">&#x274C; Installation Failed</h2>'
            . '<pre>' . htmlspecialchars($e->getMessage() . "\n\nFile: " . $e->getFile() . ':' . $e->getLine()) . '</pre>'
            . '<p><a href="/install/setup.php">&larr; Go back and try again</a></p>'
            . '</div></body></html>';
        exit;
    }
} else {
    // Show install form for GET requests
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <title>PankhCMS Setup</title>
    <style>
    body{font-family:Arial;background:#f5f5f5}
    .box{max-width:500px;margin:60px auto;background:#fff;padding:25px;border-radius:8px}
    input,select{width:100%;padding:8px;margin:8px 0}
    button{padding:10px 15px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer}
    button:hover{background:#0056b3}
    .demo-option{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;margin:6px 0;border:2px solid #e0e0e0;border-radius:6px;cursor:pointer;transition:border-color .15s}
    .demo-option:hover{border-color:#007bff}
    .demo-option input[type=radio]{width:auto;min-width:unset;margin-top:3px;flex-shrink:0;accent-color:#007bff}
    .demo-option.selected{border-color:#007bff;background:#f0f7ff}
    .demo-option-text{flex:1;min-width:0}
    .demo-label{font-weight:600;color:#222;font-size:.95em}
    .demo-desc{font-size:.82em;color:#666;margin-top:2px}
    .demo-badge{display:inline-block;padding:1px 7px;border-radius:10px;font-size:.72em;font-weight:700;margin-left:6px;vertical-align:middle}
    .badge-it{background:#dbeafe;color:#1d4ed8}
    .badge-mfg{background:#dcfce7;color:#15803d}
    .badge-blank{background:#f3f4f6;color:#6b7280}
    </style>
    </head>
    <body>
    <div class="box">
    <div style="text-align:center;margin-bottom:20px;">
        <img src="/assets/pankhcms_logo.png" alt="PankhCMS Logo" style="height:64px;display:block;margin:0 auto 10px auto;">
        <h2 style="margin:0;font-size:2em;font-weight:700;color:#007bff;">PankhCMS</h2>
        <div style="font-size:1.1em;color:#555;margin-bottom:10px;">Setup Wizard</div>
    </div>
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
    <label>Username</label>
    <input name="admin_username" required>

    <label>Email</label>
    <input name="admin_email" required>

    <label>Password</label>
    <input type="password" name="admin_password" required>

    <h3>Select Demo</h3>
    <div id="demo-options">
      <label class="demo-option selected" id="opt-corporate">
        <input type="radio" name="demo_type" value="corporate_it" checked onchange="updateDemoUI()">
        <div class="demo-option-text">
          <div class="demo-label">Varni Infoweb — Corporate IT <span class="demo-badge badge-it">IT</span></div>
          <div class="demo-desc">Pages: Home, About, Services (with sub-pages), Projects, Blog (with 4 posts), Careers, Contact — plus categories &amp; testimonials.</div>
        </div>
      </label>
      <label class="demo-option" id="opt-manufacturing">
        <input type="radio" name="demo_type" value="manufacturing" onchange="updateDemoUI()">
        <div class="demo-option-text">
          <div class="demo-label">NovaTech Instruments — Manufacturing <span class="demo-badge badge-mfg">MFG</span></div>
          <div class="demo-desc">Product catalog with categories, product detail pages &amp; image gallery. Ideal for industrial/B2B companies.</div>
        </div>
      </label>
      <label class="demo-option" id="opt-blank">
        <input type="radio" name="demo_type" value="blank" onchange="updateDemoUI()">
        <div class="demo-option-text">
          <div class="demo-label">Blank Installation <span class="demo-badge badge-blank">BLANK</span></div>
          <div class="demo-desc">No demo content. Clean start — you build everything from scratch.</div>
        </div>
      </label>
    </div>

    <button>Install CMS</button>
    </form>
    <script>
    function toggleDbFields() {
      var driver = document.getElementById('db_driver').value;
      document.getElementById('mysql-fields').style.display = (driver === 'mysql') ? '' : 'none';
    }
    document.getElementById('db_driver').addEventListener('change', toggleDbFields);
    function updateDemoUI() {
      var radios = document.querySelectorAll('#demo-options .demo-option');
      radios.forEach(function(label) {
        var radio = label.querySelector('input[type=radio]');
        label.classList.toggle('selected', radio.checked);
      });
    }
    window.onload = function() { toggleDbFields(); updateDemoUI(); };
    </script>
    </div>
    </body>
    </html>
    <?php
    exit;
}
