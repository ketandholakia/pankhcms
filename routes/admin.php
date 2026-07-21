<?php
// =========================
// Admin - Slider Images
// =========================
Flight::route('GET /admin/slider', ['App\\Controllers\\Admin\\SliderController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('GET /admin/slider/create', ['App\\Controllers\\Admin\\SliderController', 'create'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('POST /admin/slider/store', ['App\\Controllers\\Admin\\SliderController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('GET /admin/slider/edit/@id', ['App\\Controllers\\Admin\\SliderController', 'edit'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('POST /admin/slider/update/@id', ['App\\Controllers\\Admin\\SliderController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('POST /admin/slider/delete/@id', ['App\\Controllers\\Admin\\SliderController', 'delete'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
// Main Settings page
Flight::route('GET /admin/settings', ['App\Controllers\Admin\SettingsController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('POST /admin/settings/update', ['App\Controllers\Admin\SettingsController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);

// =========================
// Admin - Auth (NO middleware)
// =========================
Flight::route('GET /admin/login', ['App\Controllers\AuthController', 'showLogin']);
Flight::route('POST /admin/login', ['App\Controllers\AuthController', 'login']);
Flight::route('GET /admin/password/forgot', ['App\Controllers\AuthController', 'showForgotPassword']);
Flight::route('POST /admin/password/forgot', ['App\Controllers\AuthController', 'sendResetLink']);
Flight::route('GET /admin/password/reset', ['App\Controllers\AuthController', 'showResetPassword']);
Flight::route('POST /admin/password/reset', ['App\Controllers\AuthController', 'resetPassword']);
Flight::route('POST /admin/logout', ['App\Controllers\AuthController', 'logout']);


// =========================
// Admin - User Profile
// =========================
Flight::route('GET /admin/profile', ['App\Controllers\Admin\UserController', 'editProfile']);
Flight::route('POST /admin/profile', ['App\Controllers\Admin\UserController', 'updateProfile']);
Flight::route('POST /admin/profile/password', ['App\Controllers\Admin\UserController', 'updatePassword']);
Flight::route('POST /admin/profile/api-tokens', ['App\Controllers\Admin\UserController', 'generateApiToken']);
Flight::route('POST /admin/profile/api-tokens/@id/revoke', ['App\Controllers\Admin\UserController', 'revokeApiToken']);


// =========================
// Admin Dashboard
// =========================
Flight::route('GET /admin', ['App\Controllers\Admin\DashboardController', 'index']);


// =========================
// Admin - Backups
// =========================
Flight::route('GET /admin/backups', ['App\Controllers\Admin\BackupController', 'index']);
Flight::route('POST /admin/backups/create', ['App\Controllers\Admin\BackupController', 'create']);
Flight::route('GET /admin/backups/download/@filename', ['App\Controllers\Admin\BackupController', 'download']);
Flight::route('POST /admin/backups/delete/@filename', ['App\Controllers\Admin\BackupController', 'delete']);
Flight::route('GET /admin/backups/restore', ['App\Controllers\Admin\BackupController', 'restorePage']);
Flight::route('POST /admin/backups/restore', ['App\Controllers\Admin\BackupController', 'restore']);


// =========================
// Admin - Pages
// =========================
Flight::route('GET /admin/pages', ['App\Controllers\Admin\PageController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_pages')]);
Flight::route('GET /admin/pages/create', ['App\Controllers\Admin\PageController', 'create'])->addMiddleware([new \App\Middleware\RequirePermission('manage_pages')]);
Flight::route('POST /admin/pages', ['App\Controllers\Admin\PageController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_pages')]);
Flight::route('GET /admin/pages/@id/edit', ['App\Controllers\Admin\PageController', 'edit'])->addMiddleware([new \App\Middleware\RequirePermission('manage_pages')]);
Flight::route('POST /admin/pages/@id', ['App\Controllers\Admin\PageController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_pages')]);
Flight::route('POST /admin/pages/@id/update', ['App\Controllers\Admin\PageController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_pages')]);


// Content Types
Flight::route('GET /admin/content-types',
    ['App\\Controllers\\Admin\\ContentTypeController', 'index']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);

Flight::route('GET /admin/content-types/create',
    ['App\\Controllers\\Admin\\ContentTypeController', 'create']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);

Flight::route('POST /admin/content-types',
    ['App\\Controllers\\Admin\\ContentTypeController', 'store']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);

Flight::route('GET /admin/content-types/@id/edit',
    ['App\\Controllers\\Admin\\ContentTypeController', 'edit']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);


Flight::route('POST /admin/content-types/@id',
    ['App\\Controllers\\Admin\\ContentTypeController', 'update']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);

// Custom fields for content types
Flight::route('POST /admin/content-types/@id/fields',
    ['App\\Controllers\\Admin\\ContentTypeController', 'saveFields']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);

Flight::route('POST /admin/content-types/@id/delete',
    ['App\\Controllers\\Admin\\ContentTypeController', 'delete']
)->addMiddleware([new \App\Middleware\RequirePermission('manage_content_types')]);


// =========================
// Admin - Messages
// =========================
Flight::route('GET /admin/messages', ['App\Controllers\Admin\MessageController', 'index']);

// =========================
// Admin - Categories
// =========================
Flight::route('GET /admin/categories', ['App\Controllers\Admin\CategoryController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);
Flight::route('POST /admin/categories', ['App\Controllers\Admin\CategoryController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);
Flight::route('POST /admin/categories/@id', ['App\Controllers\Admin\CategoryController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);
Flight::route('POST /admin/categories/@id/delete', ['App\Controllers\Admin\CategoryController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);


// =========================
// Admin - Tags
// =========================
Flight::route('GET /admin/tags', ['App\Controllers\Admin\TagController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);
Flight::route('POST /admin/tags', ['App\Controllers\Admin\TagController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);
Flight::route('POST /admin/tags/@id', ['App\Controllers\Admin\TagController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);
Flight::route('POST /admin/tags/@id/delete', ['App\Controllers\Admin\TagController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_taxonomies')]);


// =========================
// Admin - Templates
// =========================
Flight::route('GET /admin/templates', ['App\Controllers\Admin\TemplateController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_templates')]);
Flight::route('GET /admin/templates/@id', ['App\Controllers\Admin\TemplateController', 'show'])->addMiddleware([new \App\Middleware\RequirePermission('manage_templates')]);
Flight::route('POST /admin/templates', ['App\Controllers\Admin\TemplateController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_templates')]);
Flight::route('POST /admin/templates/@id', ['App\Controllers\Admin\TemplateController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_templates')]);
Flight::route('POST /admin/templates/@id/delete', ['App\Controllers\Admin\TemplateController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_templates')]);


// =========================
// Admin - Themes
// =========================
Flight::route('GET /admin/themes', ['App\Controllers\Admin\ThemeController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_themes')]);
Flight::route('POST /admin/themes', ['App\Controllers\Admin\ThemeController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_themes')]);


// =========================
// Admin - Settings
// =========================
Flight::route('GET /admin/settings/seo', ['App\Controllers\Admin\SeoController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('POST /admin/settings/seo', ['App\Controllers\Admin\SeoController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);

Flight::route('GET /admin/settings/breadcrumbs', ['App\Controllers\Admin\SettingsController', 'breadcrumbsIndex'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);
Flight::route('POST /admin/settings/breadcrumbs', ['App\Controllers\Admin\SettingsController', 'breadcrumbsUpdate'])->addMiddleware([new \App\Middleware\RequirePermission('manage_settings')]);


// =========================
// Admin - Menus
// =========================
Flight::route('GET /admin/menus', ['App\Controllers\Admin\MenuController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menus', ['App\Controllers\Admin\MenuController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menus/@id', ['App\Controllers\Admin\MenuController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menus/@id/update', ['App\Controllers\Admin\MenuController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menus/@id/delete', ['App\Controllers\Admin\MenuController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);


// =========================
// Admin - Menu Items
// =========================
Flight::route('POST /admin/menu-items', ['App\Controllers\Admin\MenuItemController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menu-items/@id', ['App\Controllers\Admin\MenuItemController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menu-items/@id/update', ['App\Controllers\Admin\MenuItemController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menu-items/@id/move', ['App\Controllers\Admin\MenuItemController', 'move'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);
Flight::route('POST /admin/menu-items/@id/delete', ['App\Controllers\Admin\MenuItemController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_menus')]);


// =========================
// Admin - Uploads
// =========================
Flight::route('POST /admin/upload/image', ['App\Controllers\Admin\UploadController', 'image'])->addMiddleware([new \App\Middleware\RequirePermission('manage_media')]);


// =========================
// Admin - Media
// =========================
Flight::route('GET /admin/media', ['App\Controllers\Admin\MediaController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_media')]);
Flight::route('POST /admin/media/upload', ['App\Controllers\Admin\MediaController', 'upload'])->addMiddleware([new \App\Middleware\RequirePermission('manage_media')]);
Flight::route('POST /admin/media/@id/delete', ['App\Controllers\Admin\MediaController', 'delete'])->addMiddleware([new \App\Middleware\RequirePermission('manage_media')]);
Flight::route('GET /admin/media/picker', ['App\Controllers\Admin\MediaController', 'picker'])->addMiddleware([new \App\Middleware\RequirePermission('manage_media')]);


// =========================
// Global Admin Guard (FIXED)
// =========================
Flight::before('start', function () {

    $path = parse_url(Flight::request()->url, PHP_URL_PATH);

    $method = strtoupper((string) (Flight::request()->method ?? 'GET'));
    $isSafeMethod = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

    // CSRF protection for all state-changing admin requests (including /admin/login)
    if (str_starts_with($path, '/admin') && !$isSafeMethod) {
        csrf_require();
    }

    if (str_starts_with($path, '/admin')
        && !str_starts_with($path, '/admin/login')
        && !str_starts_with($path, '/admin/password')) {

        \App\Middleware\AdminMiddleware::handle();
    }
});


// Global Blocks CRUD
Flight::route('GET /admin/global-blocks', ['App\\Controllers\\Admin\\GlobalBlockController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('GET /admin/global-blocks/create', ['App\\Controllers\\Admin\\GlobalBlockController', 'create'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('POST /admin/global-blocks', ['App\\Controllers\\Admin\\GlobalBlockController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('GET /admin/global-blocks/@id/edit', ['App\\Controllers\\Admin\\GlobalBlockController', 'edit'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('POST /admin/global-blocks/@id', ['App\\Controllers\\Admin\\GlobalBlockController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('POST /admin/global-blocks/@id/delete', ['App\\Controllers\\Admin\\GlobalBlockController', 'delete'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);

// Block Placements CRUD
Flight::route('GET /admin/block-placements', ['App\\Controllers\\Admin\\BlockPlacementController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('GET /admin/block-placements/create', ['App\\Controllers\\Admin\\BlockPlacementController', 'create'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('POST /admin/block-placements', ['App\\Controllers\\Admin\\BlockPlacementController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('GET /admin/block-placements/@id/edit', ['App\\Controllers\\Admin\\BlockPlacementController', 'edit'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('POST /admin/block-placements/@id', ['App\\Controllers\\Admin\\BlockPlacementController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);
Flight::route('POST /admin/block-placements/@id/delete', ['App\\Controllers\\Admin\\BlockPlacementController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_blocks')]);


// Plugin admin routes
Flight::route('GET /admin/plugins', ['App\\Controllers\\Admin\\PluginController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_plugins')]);
Flight::route('POST /admin/plugins/toggle', ['App\\Controllers\\Admin\\PluginController', 'toggle'])->addMiddleware([new \App\Middleware\RequirePermission('manage_plugins')]);
Flight::route('POST /admin/plugins/upload', ['App\\Controllers\\Admin\\PluginController', 'upload'])->addMiddleware([new \App\Middleware\RequirePermission('manage_plugins')]);
Flight::route('POST /admin/plugins/uninstall', ['App\\Controllers\\Admin\\PluginController', 'uninstall'])->addMiddleware([new \App\Middleware\RequirePermission('manage_plugins')]);

// Users admin routes
Flight::route('GET /admin/users', ['App\\Controllers\\Admin\\UserController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('GET /admin/users/create', ['App\\Controllers\\Admin\\UserController', 'create'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('POST /admin/users/store', ['App\\Controllers\\Admin\\UserController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('GET /admin/users/edit/@id', ['App\\Controllers\\Admin\\UserController', 'edit'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('POST /admin/users/update/@id', ['App\\Controllers\\Admin\\UserController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('POST /admin/users/delete/@id', ['App\\Controllers\\Admin\\UserController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);

// Roles admin routes
Flight::route('GET /admin/roles', ['App\\Controllers\\Admin\\RoleController', 'index'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('GET /admin/roles/create', ['App\\Controllers\\Admin\\RoleController', 'create'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('POST /admin/roles/store', ['App\\Controllers\\Admin\\RoleController', 'store'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('GET /admin/roles/edit/@id', ['App\\Controllers\\Admin\\RoleController', 'edit'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('POST /admin/roles/update/@id', ['App\\Controllers\\Admin\\RoleController', 'update'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
Flight::route('POST /admin/roles/delete/@id', ['App\\Controllers\\Admin\\RoleController', 'destroy'])->addMiddleware([new \App\Middleware\RequirePermission('manage_users')]);
