<?php
$file = 'l:/UniWamp/www/pankhcms/routes/admin.php';
$content = file_get_contents($file);

$map = [
    '/admin/slider' => 'manage_settings',
    '/admin/settings' => 'manage_settings',
    '/admin/pages' => 'manage_pages',
    '/admin/content-types' => 'manage_content_types',
    '/admin/categories' => 'manage_taxonomies',
    '/admin/tags' => 'manage_taxonomies',
    '/admin/templates' => 'manage_templates',
    '/admin/themes' => 'manage_themes',
    '/admin/menus' => 'manage_menus',
    '/admin/menu-items' => 'manage_menus',
    '/admin/upload' => 'manage_media',
    '/admin/media' => 'manage_media',
    '/admin/global-blocks' => 'manage_blocks',
    '/admin/block-placements' => 'manage_blocks',
    '/admin/plugins' => 'manage_plugins',
];

$lines = explode("\n", $content);
foreach ($lines as &$line) {
    if (str_contains($line, 'Flight::route(')) {
        if (str_contains($line, 'addMiddleware')) continue;
        
        foreach ($map as $prefix => $perm) {
            if (str_contains($line, "'" . $prefix) || str_contains($line, "'GET " . $prefix) || str_contains($line, "'POST " . $prefix)) {
                $line = rtrim($line, ";\r\n") . "->addMiddleware([new \\App\\Middleware\\RequirePermission('$perm')]);";
                break;
            }
        }
    }
}

file_put_contents($file, implode("\n", $lines));
echo "Routes updated.\n";
