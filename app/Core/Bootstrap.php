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
        self::initBlade();
        self::loadRoutes();
    }

    private static function loadEnv()
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();
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
        $viewPaths = [
            Theme::viewPath(),
            dirname(__DIR__, 2) . "/views",
        ];
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

        try {
            if (Capsule::schema()->hasTable('settings')) {
                $siteTitle = Capsule::table('settings')->where('key', 'site_name')->value('value')
                    ?: $siteTitle;
                $siteDescription = Capsule::table('settings')->where('key', 'site_tagline')->value('value')
                    ?: $siteDescription;
                $seoDefaultTitle = Capsule::table('settings')->where('key', 'seo_default_title')->value('value')
                    ?: $seoDefaultTitle;
                $seoDefaultDescription = Capsule::table('settings')->where('key', 'seo_default_description')->value('value')
                    ?: $seoDefaultDescription;
                $ogTitleDefault = Capsule::table('settings')->where('key', 'og_title_default')->value('value')
                    ?: $ogTitleDefault;
                $breadcrumbsEnabled = Capsule::table('settings')->where('key', 'breadcrumbs_enabled')->value('value') ?: $breadcrumbsEnabled;
                $breadcrumbsType = Capsule::table('settings')->where('key', 'breadcrumbs_type')->value('value') ?: $breadcrumbsType;
                $breadcrumbsShowHome = Capsule::table('settings')->where('key', 'breadcrumbs_show_home')->value('value') ?: $breadcrumbsShowHome;
                $breadcrumbsHomeLabel = Capsule::table('settings')->where('key', 'breadcrumbs_home_label')->value('value') ?: $breadcrumbsHomeLabel;
                $breadcrumbsSeparator = Capsule::table('settings')->where('key', 'breadcrumbs_separator')->value('value') ?: $breadcrumbsSeparator;
                $breadcrumbsSchema = Capsule::table('settings')->where('key', 'breadcrumbs_schema')->value('value') ?: $breadcrumbsSchema;
                $ogDescriptionDefault = Capsule::table('settings')->where('key', 'og_description_default')->value('value')
                    ?: $ogDescriptionDefault;
                $ogImageDefault = Capsule::table('settings')->where('key', 'og_image_default')->value('value')
                    ?: $ogImageDefault;
                $canonicalBase = Capsule::table('settings')->where('key', 'canonical_base')->value('value')
                    ?: $canonicalBase;
                $robotsDefault = Capsule::table('settings')->where('key', 'robots_default')->value('value')
                    ?: $robotsDefault;
                $twitterCard = Capsule::table('settings')->where('key', 'twitter_card')->value('value')
                    ?: $twitterCard;
                $twitterSite = Capsule::table('settings')->where('key', 'twitter_site')->value('value')
                    ?: $twitterSite;
                $seoDefaultKeywords = Capsule::table('settings')->where('key', 'seo_default_keywords')->value('value')
                    ?: $seoDefaultKeywords;
                $contactMapEmbedUrl = Capsule::table('settings')->where('key', 'contact_map_embed_url')->value('value')
                    ?: $contactMapEmbedUrl;
            }
        } catch (\Throwable $e) {
            // Use fallback values when settings table is unavailable.
        }

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
        require dirname(__DIR__, 2) . "/routes/web.php";
    }

    private static function loadHelpers()
    {
        require dirname(__DIR__) . "/Helpers/functions.php";
        require dirname(__DIR__) . "/Helpers/menu.php";
        require dirname(__DIR__) . "/Helpers/breadcrumbs.php"; // New helper file
    }
}
