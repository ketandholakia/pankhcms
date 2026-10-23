<?php

namespace App\Core;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Jenssegers\Blade\Blade;

class Bootstrap
{
    public static function init()
    {
        self::loadHelpers();
        self::loadEnv();
        self::initConfig();
        self::initDatabase();
        self::applyTimezone();

        // Load plugins before routes so they can register routes and hooks
        require_once __DIR__ . '/PluginManager.php';
        \PluginManager::boot();

        self::initBlade();
        self::loadRoutes();
    }

    /**
     * Apply the `timezone` setting as PHP's default timezone, falling
     * back to UTC when the value is missing or invalid. Called from
     * init() after the database is ready and BEFORE plugins, routes and
     * controllers run, so Carbon::now() and strtotime() always agree on
     * one timezone. Public so CLI scripts that do not fully bootstrap
     * (e.g. scripts/prune-page-views.php) can apply it too.
     */
    public static function applyTimezone(): void
    {
        $timezone = 'UTC';
        try {
            $settings = \settings_all();
            $candidate = trim((string) ($settings['timezone'] ?? 'UTC'));
            if ($candidate !== '') {
                // Throws on unknown identifiers.
                new \DateTimeZone($candidate);
                $timezone = $candidate;
            }
        } catch (\Throwable $e) {
            // Invalid setting value: stay on UTC.
        }
        date_default_timezone_set($timezone);
    }

    /**
     * Load the .env file. Public so the front controller can load the
     * environment cheaply before deciding whether an anonymous request
     * can be served from the page cache (which needs APP_URL for
     * subdirectory deployments) without bootstrapping the whole app.
     */
    public static function loadEnv()
    {
        $root = dirname(__DIR__, 2);
        $envFile = $root . '/.env';
        $installLock = $root . '/public/install/lock';
        $dotenv = Dotenv::createImmutable($root);
        try {
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            if (!file_exists($envFile) && file_exists($installLock)) {
                http_response_code(500);
                echo '<h2>Configuration Error</h2>';
                echo '<p>The configuration file <code>.env</code> is missing and an installer lock file was found at <code>public/install/lock</code>.</p>';
                echo '<p>If you wish to reinstall the application, please ask your administrator to remove the lock file at <code>public/install/lock</code> and then access the installer.</p>';
                exit;
            }
            throw $e;
        }
    }

    private static function initConfig()
    {
        \Flight::set("config", require dirname(__DIR__, 2) . "/config/app.php");
    }

    private static function initDatabase()
    {
        $capsule = new Capsule();
        $config = require dirname(__DIR__, 2) . "/config/database.php";
        $connection = $config["connections"][$config["default"]];

        $capsule->addConnection($connection);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    private static function initBlade()
    {
        $viewPaths = [Theme::viewPath()];
        $defaultThemeViewPath = Theme::viewPath(Theme::default());
        if ($defaultThemeViewPath !== $viewPaths[0]) {
            $viewPaths[] = $defaultThemeViewPath;
        }
        $viewPaths[] = dirname(__DIR__, 2) . "/views";
        $cachePath = dirname(__DIR__, 2) . "/storage/cache";

        $blade = new Blade($viewPaths, $cachePath);

        $container = $blade->getContainer();

        $container->singleton("blade.compiler", function () use (
            $container,
            $cachePath,
        ) {
            return new BladeCompiler($container["files"], $cachePath);
        });

        $resolver = $blade->getEngineResolver();

        $resolver->register("blade", function () use ($container) {
            return new CompilerEngine($container["blade.compiler"]);
        });

        $siteTitle = env('APP_NAME', 'PankhCMS');
        $siteDescription = '';
        $seoDefaultTitle = '';
        $seoDefaultDescription = '';
        $breadcrumbsEnabled = '0';
        $breadcrumbsType = 'auto';
        $breadcrumbsShowHome = '1';
        $breadcrumbsHomeLabel = 'Home';
        $breadcrumbsSeparator = '/';
        $breadcrumbsSchema = '1';
        $ogTitleDefault = '';
        $ogDescriptionDefault = '';
        $ogImageDefault = '';
        $canonicalBase = env('APP_URL', '');
        $robotsDefault = 'index, follow';
        $twitterCard = 'summary_large_image';
        $twitterSite = '';
        $seoDefaultKeywords = '';
        $contactMapEmbedUrl = 'https://maps.google.com/maps?q=New%20York,%20USA&output=embed';

        // One query for every setting (settings_all() is cached per request
        // and falls back to built-in defaults when the table is missing).
        $settings = \settings_all();
        $pick = static function (array $settings, string ...$keys) {
            foreach ($keys as $key) {
                if (!empty($settings[$key])) {
                    return $settings[$key];
                }
            }
            return null;
        };

        $siteTitle = $pick($settings, 'site_title', 'site_name') ?: $siteTitle;
        $siteDescription = $pick($settings, 'tagline', 'site_tagline') ?: $siteDescription;
        $seoDefaultTitle = $pick($settings, 'seo_default_title') ?: $seoDefaultTitle;
        $seoDefaultDescription = $pick($settings, 'default_meta_description', 'seo_default_description') ?: $seoDefaultDescription;
        $ogTitleDefault = $pick($settings, 'og_title_default') ?: $ogTitleDefault;
        $breadcrumbsEnabled = $pick($settings, 'breadcrumbs_enabled') ?: $breadcrumbsEnabled;
        $breadcrumbsType = $pick($settings, 'breadcrumbs_type') ?: $breadcrumbsType;
        $breadcrumbsShowHome = $pick($settings, 'breadcrumbs_show_home') ?: $breadcrumbsShowHome;
        $breadcrumbsHomeLabel = $pick($settings, 'breadcrumbs_home_label') ?: $breadcrumbsHomeLabel;
        $breadcrumbsSeparator = $pick($settings, 'breadcrumbs_separator') ?: $breadcrumbsSeparator;
        $breadcrumbsSchema = $pick($settings, 'breadcrumbs_schema') ?: $breadcrumbsSchema;
        $ogDescriptionDefault = $pick($settings, 'og_description_default') ?: $ogDescriptionDefault;
        $ogImageDefault = $pick($settings, 'og_image', 'og_image_default') ?: $ogImageDefault;
        $canonicalBase = $pick($settings, 'site_url', 'canonical_base') ?: $canonicalBase;
        $robotsDefault = $pick($settings, 'robots_default') ?: $robotsDefault;
        $twitterCard = $pick($settings, 'twitter_card') ?: $twitterCard;
        $twitterSite = $pick($settings, 'twitter_site') ?: $twitterSite;
        $seoDefaultKeywords = $pick($settings, 'default_meta_keywords', 'seo_default_keywords') ?: $seoDefaultKeywords;
        $contactMapEmbedUrl = $pick($settings, 'contact_map_embed_url') ?: $contactMapEmbedUrl;

        $blade->share('site_title', $siteTitle);
        $blade->share('site_description', $siteDescription);
        $blade->share('seo_default_title', $seoDefaultTitle);
        $blade->share('seo_default_description', $seoDefaultDescription);
        $blade->share('breadcrumbs_enabled', $breadcrumbsEnabled);
        $blade->share('breadcrumbs_type', $breadcrumbsType);
        $blade->share('breadcrumbs_show_home', $breadcrumbsShowHome);
        $blade->share('breadcrumbs_home_label', $breadcrumbsHomeLabel);
        $blade->share('breadcrumbs_separator', $breadcrumbsSeparator);
        $blade->share('breadcrumbs_schema', $breadcrumbsSchema);
        $blade->share('og_title_default', $ogTitleDefault);
        $blade->share('og_description_default', $ogDescriptionDefault);
        $blade->share('og_image_default', $ogImageDefault);
        $blade->share('canonical_base', $canonicalBase);
        $blade->share('robots_default', $robotsDefault);
        $blade->share('twitter_card', $twitterCard);
        $blade->share('twitter_site', $twitterSite);
        $blade->share('seo_default_keywords', $seoDefaultKeywords);
        $blade->share('contact_map_embed_url', $contactMapEmbedUrl);
        $blade->share('widgets', []);
        $blade->share('hero', []);

        \Flight::set("blade", $blade);
    }

    private static function loadRoutes()
    {
        require dirname(__DIR__, 2) . "/routes/admin.php";
        require dirname(__DIR__, 2) . "/routes/api.php";
        require dirname(__DIR__, 2) . "/routes/web.php";

        // Invalidate the full-page cache whenever content that can appear
        // on rendered pages changes.
        $clearPageCache = function () {
            \App\Middleware\PageCache::clear();
        };
        \Event::listen('page.created', $clearPageCache);
        \Event::listen('page.updated', $clearPageCache);
        \Event::listen('page.deleted', $clearPageCache);
        \Event::listen('page.published', $clearPageCache);
    }

    private static function loadHelpers()
    {
        require_once dirname(__DIR__) . "/Helpers/functions.php";
        require_once dirname(__DIR__) . "/Helpers/menu.php";
        require_once dirname(__DIR__) . "/Helpers/breadcrumbs.php"; // New helper file
        require_once dirname(__DIR__) . "/Helpers/global_blocks.php";
    }
}
