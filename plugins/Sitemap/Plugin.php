<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class SitemapPlugin
{
    public function boot()
    {
        Flight::route('GET /sitemap.xml', function () {
            // Get all published pages
            $pages = Capsule::table('pages')
                ->where('status', 'published')
                ->get();
            
            $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');

            // Generate XML
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

            foreach ($pages as $page) {
                $url = $xml->addChild('url');
                // The homepage is usually empty slug or '/'
                $path = ($page->slug === '/' || $page->slug === 'home' || $page->slug === '') ? '' : '/' . $page->slug;
                
                $url->addChild('loc', htmlspecialchars($baseUrl . $path));
                
                $date = $page->updated_at ? date('c', strtotime($page->updated_at)) : date('c');
                $url->addChild('lastmod', $date);
                
                // Priority logic based on parent vs child pages
                $priority = $page->parent_id ? '0.6' : '0.8';
                if ($path === '') $priority = '1.0';
                
                $url->addChild('priority', $priority);
            }

            Flight::response()->header('Content-Type', 'text/xml');
            echo $xml->asXML();
        });
    }
}
