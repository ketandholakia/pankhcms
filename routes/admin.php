<?php

// =========================
// Admin - Auth (NO middleware)
// =========================
Flight::route('GET /admin/login', ['App\Controllers\AuthController', 'showLogin']);
Flight::route('POST /admin/login', ['App\Controllers\AuthController', 'login']);
Flight::route('POST /admin/logout', ['App\Controllers\AuthController', 'logout']);


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
Flight::route('GET /admin/pages', ['App\Controllers\Admin\PageController', 'index']);
Flight::route('GET /admin/pages/create', ['App\Controllers\Admin\PageController', 'create']);
Flight::route('POST /admin/pages', ['App\Controllers\Admin\PageController', 'store']);
Flight::route('GET /admin/pages/@id/edit', ['App\Controllers\Admin\PageController', 'edit']);
Flight::route('POST /admin/pages/@id', ['App\Controllers\Admin\PageController', 'update']);
Flight::route('POST /admin/pages/@id/update', ['App\Controllers\Admin\PageController', 'update']);


// Content Types
Flight::route('GET /admin/content-types',
    ['App\\Controllers\\Admin\\ContentTypeController', 'index']
);

Flight::route('GET /admin/content-types/create',
    ['App\\Controllers\\Admin\\ContentTypeController', 'create']
);

Flight::route('POST /admin/content-types',
    ['App\\Controllers\\Admin\\ContentTypeController', 'store']
);

Flight::route('GET /admin/content-types/@id/edit',
    ['App\\Controllers\\Admin\\ContentTypeController', 'edit']
);


Flight::route('POST /admin/content-types/@id',
    ['App\\Controllers\\Admin\\ContentTypeController', 'update']
);

// Custom fields for content types
Flight::route('POST /admin/content-types/@id/fields',
    ['App\\Controllers\\Admin\\ContentTypeController', 'saveFields']
);

Flight::route('POST /admin/content-types/@id/delete',
    ['App\\Controllers\\Admin\\ContentTypeController', 'delete']
);


// =========================
// Admin - Messages
// =========================
Flight::route('GET /admin/messages', ['App\Controllers\Admin\MessageController', 'index']);


// =========================
// Admin - Categories
// =========================
Flight::route('GET /admin/categories', ['App\Controllers\Admin\CategoryController', 'index']);
Flight::route('POST /admin/categories', ['App\Controllers\Admin\CategoryController', 'store']);
Flight::route('POST /admin/categories/@id', ['App\Controllers\Admin\CategoryController', 'update']);
Flight::route('POST /admin/categories/@id/delete', ['App\Controllers\Admin\CategoryController', 'destroy']);


// =========================
// Admin - Tags
// =========================
Flight::route('GET /admin/tags', ['App\Controllers\Admin\TagController', 'index']);
Flight::route('POST /admin/tags', ['App\Controllers\Admin\TagController', 'store']);
Flight::route('POST /admin/tags/@id', ['App\Controllers\Admin\TagController', 'update']);
Flight::route('POST /admin/tags/@id/delete', ['App\Controllers\Admin\TagController', 'destroy']);


// =========================
// Admin - Templates
// =========================
Flight::route('GET /admin/templates', ['App\Controllers\Admin\TemplateController', 'index']);
Flight::route('GET /admin/templates/@id', ['App\Controllers\Admin\TemplateController', 'show']);
Flight::route('POST /admin/templates', ['App\Controllers\Admin\TemplateController', 'store']);
Flight::route('POST /admin/templates/@id', ['App\Controllers\Admin\TemplateController', 'update']);
Flight::route('POST /admin/templates/@id/delete', ['App\Controllers\Admin\TemplateController', 'destroy']);


// =========================
// Admin - Themes
// =========================
Flight::route('GET /admin/themes', ['App\Controllers\Admin\ThemeController', 'index']);
Flight::route('POST /admin/themes', ['App\Controllers\Admin\ThemeController', 'update']);


// =========================
// Admin - Settings
// =========================
Flight::route('GET /admin/settings/seo', ['App\Controllers\Admin\SeoController', 'index']);
Flight::route('POST /admin/settings/seo', ['App\Controllers\Admin\SeoController', 'update']);

Flight::route('GET /admin/settings/breadcrumbs', ['App\Controllers\Admin\SettingsController', 'breadcrumbsIndex']);
Flight::route('POST /admin/settings/breadcrumbs', ['App\Controllers\Admin\SettingsController', 'breadcrumbsUpdate']);


// =========================
// Admin - Menus
// =========================
Flight::route('GET /admin/menus', ['App\Controllers\Admin\MenuController', 'index']);
Flight::route('POST /admin/menus', ['App\Controllers\Admin\MenuController', 'store']);
Flight::route('POST /admin/menus/@id', ['App\Controllers\Admin\MenuController', 'update']);
Flight::route('POST /admin/menus/@id/update', ['App\Controllers\Admin\MenuController', 'update']);
Flight::route('POST /admin/menus/@id/delete', ['App\Controllers\Admin\MenuController', 'destroy']);


// =========================
// Admin - Menu Items
// =========================
Flight::route('POST /admin/menu-items', ['App\Controllers\Admin\MenuItemController', 'store']);
Flight::route('POST /admin/menu-items/@id', ['App\Controllers\Admin\MenuItemController', 'update']);
Flight::route('POST /admin/menu-items/@id/update', ['App\Controllers\Admin\MenuItemController', 'update']);
Flight::route('POST /admin/menu-items/@id/move', ['App\Controllers\Admin\MenuItemController', 'move']);
Flight::route('POST /admin/menu-items/@id/delete', ['App\Controllers\Admin\MenuItemController', 'destroy']);


// =========================
// Admin - Uploads
// =========================
Flight::route('POST /admin/upload/image', ['App\Controllers\Admin\UploadController', 'image']);


// =========================
// Admin - Media
// =========================
Flight::route('GET /admin/media', ['App\Controllers\Admin\MediaController', 'index']);
Flight::route('POST /admin/media/upload', ['App\Controllers\Admin\MediaController', 'upload']);
Flight::route('POST /admin/media/@id/delete', ['App\Controllers\Admin\MediaController', 'delete']);
Flight::route('GET /admin/media/picker', ['App\Controllers\Admin\MediaController', 'picker']);


// =========================
// Global Admin Guard (FIXED)
// =========================
Flight::before('start', function () {

    $path = parse_url(Flight::request()->url, PHP_URL_PATH);

    if (str_starts_with($path, '/admin')
        && !str_starts_with($path, '/admin/login')) {

        \App\Middleware\AdminMiddleware::handle();
    }
});
