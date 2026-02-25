
# PankhCMS

PankhCMS — a lightweight, extensible PHP CMS built with FlightPHP and Jenssegers Blade. This README is written for developers and maintainers who need to run, extend, and build plugins for the application.

**Quick links**
- **Core bootstrap:** [app/Core/Bootstrap.php](app/Core/Bootstrap.php)
- **Plugin manager:** [app/Core/PluginManager.php](app/Core/PluginManager.php)
- **Base plugin class:** [app/Core/BasePlugin.php](app/Core/BasePlugin.php)
- **Admin plugin controller:** [app/Controllers/Admin/PluginController.php](app/Controllers/Admin/PluginController.php)
- **Admin layout:** [views/layouts/admin.blade.php](views/layouts/admin.blade.php)

**Table of contents**
- **Overview**
- **Requirements**
- **Local setup**
- **Project layout**
- **Plugin system (developer guide)**
- **Admin UI & plugin management**
- **Migrations & database**
- **Testing and troubleshooting**
- **Contributing**

**Overview**
PankhCMS provides a small, focused core and a plugin architecture that enables independently packaged features. The system supports:
- Route and admin page registration from plugins
- Plugin lifecycle: register, boot, activate, deactivate, uninstall
- Plugin migrations and DB entries (plugins table)
- Hook and event registries for extension points
- Admin menu registration so plugins can add their own admin pages

This README focuses on how to run the app locally, how the plugin system works, and how to develop plugins.

**Requirements**
- PHP 8.x
- Composer
- MySQL or SQLite (configured via config/database.php and .env)
- Web server (Apache, Nginx) or PHP built-in server for development

**Local setup**
1. Copy the environment example to create your .env and set DB credentials and APP_URL:

   cp .env.example .env

2. Install PHP dependencies:

   composer install

3. Prepare storage and cache dirs:

   mkdir -p storage/cache storage/logs public/uploads
   chown -R $(whoami):$(whoami) storage public/uploads

4. Run migrations (project ships migration scripts under database/migrations and database/sqlite-php-scripts). If you use MySQL and don't have the mysql client, use PHP PDO scripts or the included migration runner:

   php scripts/run_migrations.php

5. Start a local PHP server for development (optional):

   php -S 127.0.0.1:8000 -t public

Open the site at the configured APP_URL or http://127.0.0.1:8000

**Project layout (important paths)**
- Root: application entrypoints in `public/` and `htdocs/`.
- Core code: `app/` — controllers, core classes, helpers.
- Views: `views/` and `resources/views/` (legacy fallback).
- Plugins: `plugins/` — discoverable plugin packages.
- Themes: `themes/` — front-end themes and view overrides.
- Storage: `storage/` — cache, logs, backups.

Key core modules:
- `app/Core/Bootstrap.php` — bootstraps environment, database, Blade, and plugins.
- `app/Core/PluginManager.php` — discovers and boots active plugins, and exposes lifecycle methods `activate`, `deactivate`, `uninstall`.
- `app/Core/BasePlugin.php` — base class plugins should extend to implement register/boot/activate/deactivate/uninstall.
- `app/Core/Hooks.php`, `app/Core/Event.php` — extension points and event dispatcher.

**Plugin system — developer guide**
Plugins are packaged under the `plugins/` directory. A plugin typically contains:
- `plugin.json` — metadata (slug, name, version, main file path, description)
- `Plugin.php` — plugin class (ideally extends `BasePlugin`)
- `routes.php` and `admin.php` — optional route registration for frontend and admin pages
- `views/` — plugin-specific Blade views
- `assets/` — JS/CSS that can be published or served from the plugin
- `migrations/` or `migrations/install.sql` — DB schema for plugin activation

Minimal plugin.json example:

```json
{
  "slug": "contact-form",
  "name": "Contact Form",
  "version": "1.0.0",
  "main": "Plugin.php",
  "description": "A simple contact form plugin."
}
```

Plugin class notes:
- Extend `app/Core/BasePlugin.php` when possible and implement `register()` to register hooks/menus and `boot()` to attach runtime behavior (routes, event listeners).
- On activation the `PluginManager::activate($slug)` method will call plugin `activate()` and insert/update the `plugins` table with `active = 1`.
- Plugins should provide migration SQL in `migrations/` and can run DB setup inside `activate()` or rely on the PluginManager to run packaged SQL files when available.

Registries and integration points:
- `AdminMenu::add()` — register admin sidebar entries; entries appear under the new "Extensions" group in the admin sidebar.
- `BlockRegistry`, `ContentTypeRegistry` — register CMS blocks and content types programmatically.
- `Hooks::add('hook.name', $callable)` and `Hooks::run('hook.name', $args)` — basic hook mechanism for loose coupling between plugins and core.

**Admin UI & plugin management**
- Plugin management UI is at `/admin/plugins` and backed by `app/Controllers/Admin/PluginController.php`.
- Actions supported from UI: upload (zip), activate/deactivate, uninstall. All admin forms include CSRF protection.

If you are adding an admin link from your plugin, call `AdminMenu::add(['href' => '/admin/your-plugin', 'icon' => 'plug', 'label' => 'Your Plugin'])` in your plugin's `register()` method.

**Migrations & database**
- The application uses Illuminate Database (Capsule) for DB access. DB config is in `config/database.php`.
- A `plugins` table stores discovered/installed plugins; migrations exist under `database/migrations/`.
- If your plugin ships SQL, prefer a single `install.sql` or numbered SQL files in `migrations/` and run them from `activate()`.

**Testing and troubleshooting**
- Lint PHP files quickly:

  php -l path/to/file.php

- Blade views are cached in `storage/cache/`. If you make view changes and do not see updates, clear the cache:

  rm -rf storage/cache/*

- File permission problems are the most common source of issues (uploads, themes, plugin removal). Ensure the web user or your dev user owns `storage/`, `public/uploads/`, and `themes/` where needed.

**Contributing**
- Follow existing code style; keep changes small and focused.
- If adding a plugin feature, provide a sample plugin under `plugins/` and add install SQL to `database/migrations` if it modifies core schemas.

**Where to look next**
- Implementations and helpers in `app/Core/`:
  - [app/Core/PluginManager.php](app/Core/PluginManager.php)
  - [app/Core/BasePlugin.php](app/Core/BasePlugin.php)
  - [app/Core/AdminMenu.php](app/Core/AdminMenu.php)
- Admin UI: [app/Controllers/Admin/PluginController.php](app/Controllers/Admin/PluginController.php)

---
If you'd like, I can:
- add a short quickstart for plugin authors with a minimal plugin scaffold
- generate a CONTRIBUTING.md and CODE_OF_CONDUCT
- create a CI job to run PHP linting across the repository

Tell me which of the follow-ups you'd like next.

## 🚀 About

PankhCMS provides a fast, simple, and extensible platform for managing content. It leverages FlightPHP, Eloquent ORM, and Blade templating for a modern PHP experience.

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Composer**: For dependency management
- **Web Server**: Apache, Nginx, or PHP's built-in server

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/PankhCMS.git
   cd PankhCMS
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configuration**
   - Copy `.env.example` to `.env` and set your environment variables.
   - Configure `config/app.php` and `config/database.php` as needed.
   - Ensure your web server points to the `public/` folder.

4. **Database Migration**
   - Run migration scripts in `database/sqlite-php-scripts/`:
     ```bash
     php database/sqlite-php-scripts/create-tables.php
     php database/sqlite-php-scripts/seed.php
     ```

5. **Start the development server**
   ```bash
   php -S localhost:8000 -t public
   ```

## 🔒 Security Features

- **CSRF Protection**: All admin POST routes are protected, including AJAX and uploads.
- **Session Hardening**: Secure cookie settings, strict mode, and `SameSite=Lax`.
- **File Upload Hardening**: Server-side MIME sniffing, size limits, safe filenames/extensions.
- **Login Rate Limiting**: Exponential backoff after 5 failed attempts (IP + username).
- **Password Policy**: Minimum 10 characters, must include uppercase, lowercase, number, and symbol.
- **Password Hashing**: All passwords stored with `password_hash()` (BCrypt/Argon2); automatic rehash on login.

## 📂 Project Structure

- `app/` - Application logic (controllers, models, helpers)
- `views/` - Blade templates
- `public/` - Web server entry point (assets, index.php)
- `database/` - Migration and seed scripts
- `storage/` - Cache, logs, uploads
- `config/` - App and database configuration
- `vendor/` - Composer dependencies

## 💻 Usage

- Access the admin panel at `/admin`.
- Media library supports responsive thumbnails and modal enlarge preview.
- All uploads are validated and securely stored.

## 🩺 Troubleshooting

- **Installer locked**: Remove `.env` and `public/install/lock` to rerun installer.
- **Session issues**: Clear cookies and ensure secure settings.
- **Login issues**: Wait for rate limit to expire or reset password with a strong `ADMIN_PASSWORD`.

## 👩‍💻 Developer & Contributor Instructions

### Local Development

1. Fork and clone the repository.
2. Run `composer install` to install dependencies.
3. Configure `.env` and database settings as above.
4. Run migration scripts to set up the schema.
5. Start the PHP built-in server or configure Apache/Nginx to serve from `public/`.

### Coding Standards

- Use PSR-4 autoloading for new classes.
- Use Eloquent ORM for database access.
- Use Blade for templating.
- Keep controllers thin; use helpers and models for business logic.
- Write secure code: validate input, escape output, use prepared statements.

### Security Checklist

- Always use `password_hash()` for storing passwords.
- Validate file uploads with MIME sniffing and extension mapping.
- Use CSRF tokens in all forms and AJAX requests.
- Harden session settings (see `session_init()` in helpers).
- Use rate limiting for login and sensitive endpoints.

### Contributing

- Fork the repo and create a feature branch.
- Make your changes and add tests if possible.
- Run `php -l` and `composer test` (if tests exist) before submitting.
- Submit a Pull Request with a clear description of your changes.

## 📄 License

MIT License.