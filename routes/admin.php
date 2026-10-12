<?php

use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\BlockPlacementController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\ContentTypeController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\GlobalBlockController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\MenuItemController;
use App\Controllers\Admin\MessageController;
use App\Controllers\Admin\PageController;
use App\Controllers\Admin\PluginController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SeoController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\SliderController;
use App\Controllers\Admin\TagController;
use App\Controllers\Admin\TemplateController;
use App\Controllers\Admin\ThemeController;
use App\Controllers\Admin\UploadController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Middleware\AdminMiddleware;
use App\Middleware\RequirePermission;

/*
 * CSRF protection: every non-safe request under /admin — including login
 * and password reset — must carry a valid token. The match is segment
 * exact, so a public page slugged "administration" is NOT an admin path.
 */
Flight::before('start', function () {
    $path = parse_url(Flight::request()->url, PHP_URL_PATH) ?: '/';
    $method = strtoupper((string) (Flight::request()->method ?? 'GET'));

    if (preg_match('#^/admin(/|$)#', $path) && !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        csrf_require();
    }
});

/*
 * Unauthenticated endpoints (login + password reset). Everything else
 * under /admin is registered inside the group below, which requires an
 * authenticated session before any route in it can run.
 */
Flight::route('GET /admin/login', [AuthController::class, 'showLogin']);
Flight::route('POST /admin/login', [AuthController::class, 'login']);
Flight::route('GET /admin/password/forgot', [AuthController::class, 'showForgotPassword']);
Flight::route('POST /admin/password/forgot', [AuthController::class, 'sendResetLink']);
Flight::route('GET /admin/password/reset', [AuthController::class, 'showResetPassword']);
Flight::route('POST /admin/password/reset', [AuthController::class, 'resetPassword']);

/*
 * Deny-by-default admin area: no route is reachable without a valid
 * session. Finer-grained permissions are attached per route.
 */
Flight::group('/admin', function () {

    // Dashboard and profile (session-only, no permission required)
    Flight::route('GET /', [DashboardController::class, 'index']);

    Flight::route('GET /profile', [UserController::class, 'editProfile']);
    Flight::route('POST /profile', [UserController::class, 'updateProfile']);
    Flight::route('POST /profile/password', [UserController::class, 'updatePassword']);
    Flight::route('POST /profile/api-tokens', [UserController::class, 'generateApiToken']);
    Flight::route('POST /profile/api-tokens/@id/revoke', [UserController::class, 'revokeApiToken']);
    Flight::route('POST /logout', [AuthController::class, 'logout']);

    // Slider Images
    Flight::route('GET /slider', [SliderController::class, 'index'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('GET /slider/create', [SliderController::class, 'create'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('POST /slider/store', [SliderController::class, 'store'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('GET /slider/edit/@id', [SliderController::class, 'edit'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('POST /slider/update/@id', [SliderController::class, 'update'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('POST /slider/delete/@id', [SliderController::class, 'delete'])->addMiddleware([new RequirePermission('manage_settings')]);

    // Settings
    Flight::route('GET /settings', [SettingsController::class, 'index'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('POST /settings/update', [SettingsController::class, 'update'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('GET /settings/seo', [SeoController::class, 'index'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('POST /settings/seo', [SeoController::class, 'update'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('GET /settings/breadcrumbs', [SettingsController::class, 'breadcrumbsIndex'])->addMiddleware([new RequirePermission('manage_settings')]);
    Flight::route('POST /settings/breadcrumbs', [SettingsController::class, 'breadcrumbsUpdate'])->addMiddleware([new RequirePermission('manage_settings')]);

    // Backups
    Flight::route('GET /backups', [BackupController::class, 'index'])->addMiddleware([new RequirePermission('manage_backups')]);
    Flight::route('POST /backups/create', [BackupController::class, 'create'])->addMiddleware([new RequirePermission('manage_backups')]);
    Flight::route('GET /backups/download/@filename', [BackupController::class, 'download'])->addMiddleware([new RequirePermission('manage_backups')]);
    Flight::route('POST /backups/delete/@filename', [BackupController::class, 'delete'])->addMiddleware([new RequirePermission('manage_backups')]);
    Flight::route('GET /backups/restore', [BackupController::class, 'restorePage'])->addMiddleware([new RequirePermission('manage_backups')]);
    Flight::route('POST /backups/restore', [BackupController::class, 'restore'])->addMiddleware([new RequirePermission('manage_backups')]);

    // Pages
    Flight::route('GET /pages', [PageController::class, 'index'])->addMiddleware([new RequirePermission('manage_pages')]);
    Flight::route('GET /pages/create', [PageController::class, 'create'])->addMiddleware([new RequirePermission('manage_pages')]);
    Flight::route('POST /pages', [PageController::class, 'store'])->addMiddleware([new RequirePermission('manage_pages')]);
    Flight::route('GET /pages/@id/edit', [PageController::class, 'edit'])->addMiddleware([new RequirePermission('manage_pages')]);
    Flight::route('POST /pages/@id', [PageController::class, 'update'])->addMiddleware([new RequirePermission('manage_pages')]);
    Flight::route('POST /pages/@id/update', [PageController::class, 'update'])->addMiddleware([new RequirePermission('manage_pages')]);
    Flight::route('POST /pages/@id/delete', [PageController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_pages')]);

    // Content Types
    Flight::route('GET /content-types', [ContentTypeController::class, 'index'])->addMiddleware([new RequirePermission('manage_content_types')]);
    Flight::route('GET /content-types/create', [ContentTypeController::class, 'create'])->addMiddleware([new RequirePermission('manage_content_types')]);
    Flight::route('POST /content-types', [ContentTypeController::class, 'store'])->addMiddleware([new RequirePermission('manage_content_types')]);
    Flight::route('GET /content-types/@id/edit', [ContentTypeController::class, 'edit'])->addMiddleware([new RequirePermission('manage_content_types')]);
    Flight::route('POST /content-types/@id', [ContentTypeController::class, 'update'])->addMiddleware([new RequirePermission('manage_content_types')]);
    Flight::route('POST /content-types/@id/fields', [ContentTypeController::class, 'saveFields'])->addMiddleware([new RequirePermission('manage_content_types')]);
    Flight::route('POST /content-types/@id/delete', [ContentTypeController::class, 'delete'])->addMiddleware([new RequirePermission('manage_content_types')]);

    // Messages
    Flight::route('GET /messages', [MessageController::class, 'index'])->addMiddleware([new RequirePermission('manage_messages')]);

    // Categories
    Flight::route('GET /categories', [CategoryController::class, 'index'])->addMiddleware([new RequirePermission('manage_taxonomies')]);
    Flight::route('POST /categories', [CategoryController::class, 'store'])->addMiddleware([new RequirePermission('manage_taxonomies')]);
    Flight::route('POST /categories/@id', [CategoryController::class, 'update'])->addMiddleware([new RequirePermission('manage_taxonomies')]);
    Flight::route('POST /categories/@id/delete', [CategoryController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_taxonomies')]);

    // Tags
    Flight::route('GET /tags', [TagController::class, 'index'])->addMiddleware([new RequirePermission('manage_taxonomies')]);
    Flight::route('POST /tags', [TagController::class, 'store'])->addMiddleware([new RequirePermission('manage_taxonomies')]);
    Flight::route('POST /tags/@id', [TagController::class, 'update'])->addMiddleware([new RequirePermission('manage_taxonomies')]);
    Flight::route('POST /tags/@id/delete', [TagController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_taxonomies')]);

    // Templates
    Flight::route('GET /templates', [TemplateController::class, 'index'])->addMiddleware([new RequirePermission('manage_templates')]);
    Flight::route('GET /templates/@id', [TemplateController::class, 'show'])->addMiddleware([new RequirePermission('manage_templates')]);
    Flight::route('POST /templates', [TemplateController::class, 'store'])->addMiddleware([new RequirePermission('manage_templates')]);
    Flight::route('POST /templates/@id', [TemplateController::class, 'update'])->addMiddleware([new RequirePermission('manage_templates')]);
    Flight::route('POST /templates/@id/delete', [TemplateController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_templates')]);

    // Themes
    Flight::route('GET /themes', [ThemeController::class, 'index'])->addMiddleware([new RequirePermission('manage_themes')]);
    Flight::route('POST /themes', [ThemeController::class, 'update'])->addMiddleware([new RequirePermission('manage_themes')]);

    // Menus
    Flight::route('GET /menus', [MenuController::class, 'index'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menus', [MenuController::class, 'store'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menus/@id', [MenuController::class, 'update'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menus/@id/update', [MenuController::class, 'update'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menus/@id/delete', [MenuController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_menus')]);

    // Menu Items
    Flight::route('POST /menu-items', [MenuItemController::class, 'store'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menu-items/@id', [MenuItemController::class, 'update'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menu-items/@id/update', [MenuItemController::class, 'update'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menu-items/@id/move', [MenuItemController::class, 'move'])->addMiddleware([new RequirePermission('manage_menus')]);
    Flight::route('POST /menu-items/@id/delete', [MenuItemController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_menus')]);

    // Uploads
    Flight::route('POST /upload/image', [UploadController::class, 'image'])->addMiddleware([new RequirePermission('manage_media')]);

    // Media
    Flight::route('GET /media', [MediaController::class, 'index'])->addMiddleware([new RequirePermission('manage_media')]);
    Flight::route('POST /media/upload', [MediaController::class, 'upload'])->addMiddleware([new RequirePermission('manage_media')]);
    Flight::route('POST /media/@id/delete', [MediaController::class, 'delete'])->addMiddleware([new RequirePermission('manage_media')]);
    Flight::route('GET /media/picker', [MediaController::class, 'picker'])->addMiddleware([new RequirePermission('manage_media')]);

    // Global Blocks
    Flight::route('GET /global-blocks', [GlobalBlockController::class, 'index'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('GET /global-blocks/create', [GlobalBlockController::class, 'create'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('POST /global-blocks', [GlobalBlockController::class, 'store'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('GET /global-blocks/@id/edit', [GlobalBlockController::class, 'edit'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('POST /global-blocks/@id', [GlobalBlockController::class, 'update'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('POST /global-blocks/@id/delete', [GlobalBlockController::class, 'delete'])->addMiddleware([new RequirePermission('manage_blocks')]);

    // Block Placements
    Flight::route('GET /block-placements', [BlockPlacementController::class, 'index'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('GET /block-placements/create', [BlockPlacementController::class, 'create'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('POST /block-placements', [BlockPlacementController::class, 'store'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('GET /block-placements/@id/edit', [BlockPlacementController::class, 'edit'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('POST /block-placements/@id', [BlockPlacementController::class, 'update'])->addMiddleware([new RequirePermission('manage_blocks')]);
    Flight::route('POST /block-placements/@id/delete', [BlockPlacementController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_blocks')]);

    // Plugins
    Flight::route('GET /plugins', [PluginController::class, 'index'])->addMiddleware([new RequirePermission('manage_plugins')]);
    Flight::route('POST /plugins/toggle', [PluginController::class, 'toggle'])->addMiddleware([new RequirePermission('manage_plugins')]);
    Flight::route('POST /plugins/upload', [PluginController::class, 'upload'])->addMiddleware([new RequirePermission('manage_plugins')]);
    Flight::route('POST /plugins/uninstall', [PluginController::class, 'uninstall'])->addMiddleware([new RequirePermission('manage_plugins')]);

    // Users
    Flight::route('GET /users', [UserController::class, 'index'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('GET /users/create', [UserController::class, 'create'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('POST /users/store', [UserController::class, 'store'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('GET /users/edit/@id', [UserController::class, 'edit'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('POST /users/update/@id', [UserController::class, 'update'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('POST /users/delete/@id', [UserController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_users')]);

    // Roles
    Flight::route('GET /roles', [RoleController::class, 'index'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('GET /roles/create', [RoleController::class, 'create'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('POST /roles/store', [RoleController::class, 'store'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('GET /roles/edit/@id', [RoleController::class, 'edit'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('POST /roles/update/@id', [RoleController::class, 'update'])->addMiddleware([new RequirePermission('manage_users')]);
    Flight::route('POST /roles/delete/@id', [RoleController::class, 'destroy'])->addMiddleware([new RequirePermission('manage_users')]);

}, [new AdminMiddleware()]);
