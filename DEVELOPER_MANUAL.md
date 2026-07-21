# PankhCMS Developer Manual

## 1. Introduction

PankhCMS is a modular, PHP-based content management system designed for flexibility, speed, and extensibility. It uses FlightPHP for routing, Eloquent ORM for database operations, and Jenssegers Blade for templating.

This manual serves as the primary resource for backend developers, core maintainers, and plugin authors.

---

## 2. Architecture & Structure

PankhCMS is structured to keep the core small while allowing robust extensions via plugins and themes.

### Project Layout

- `app/` — Controllers, models, core classes, and helpers.
  - `app/Core/Bootstrap.php` — Bootstraps environment, database, Blade, and plugins.
  - `app/Core/PluginManager.php` — Discovers and boots active plugins, managing their lifecycle (`activate`, `deactivate`, `uninstall`).
  - `app/Core/BasePlugin.php` — Base class plugins should extend.
  - `app/Core/Hooks.php` & `app/Core/Event.php` — Extension points and event dispatcher.
- `views/` — Global Blade templates and admin interface views.
- `public/` — Web server entry point (`index.php`) and published assets.
- `database/` — Migration and seed scripts.
- `storage/` — Cache, logs, uploads.
- `config/` — Application and database configuration.
- `plugins/` — Discoverable plugin packages.
- `themes/` — Front-end themes and view overrides.
- `vendor/` — Composer dependencies.

---

## 3. Plugin System — Developer Guide

PankhCMS features a powerful plugin architecture that enables independently packaged features.

### Creating a Plugin

Plugins reside in the `plugins/` directory. A standard plugin contains:

- `plugin.json` — Metadata (slug, name, version, main file path, description)
- `Plugin.php` — The main plugin class (must extend `BasePlugin`)
- `routes.php` / `admin.php` — (Optional) Route registration for frontend and admin pages
- `views/` — (Optional) Plugin-specific Blade views
- `assets/` — (Optional) JS/CSS to be served
- `migrations/` or `migrations/install.sql` — DB schema for plugin activation

#### Example `plugin.json`

```json
{
  "slug": "contact-form",
  "name": "Contact Form",
  "version": "1.0.0",
  "main": "Plugin.php",
  "description": "A simple contact form plugin."
}
```

### Plugin Lifecycle & Class

Your main plugin file (e.g., `Plugin.php`) should extend `app\Core\BasePlugin`. 

- **`register()`**: Use this to register hooks, events, and admin menus.
- **`boot()`**: Use this to attach runtime behavior, such as including route files.
- **`activate()`**: Called when the plugin is activated via the admin panel. Use it to run migrations or seed data.
- **`deactivate()`**: Called when deactivated. 
- **`uninstall()`**: Called when uninstalled. Clean up your tables and files here.

### Integration Points

#### Hooks and Events

PankhCMS uses a hook system for loose coupling between plugins and the core.

- Register a hook: `Hooks::add('hook.name', $callable)`
- Trigger a hook: `Hooks::run('hook.name', $args)`

#### Admin Menus

To add your plugin to the admin sidebar under "Extensions", call the `AdminMenu::add` method in your plugin's `register()` method:

```php
AdminMenu::add([
    'href' => '/admin/your-plugin', 
    'icon' => 'plug', 
    'label' => 'Your Plugin'
]);
```

#### Plugin Migrations

If your plugin requires database tables, provide SQL in the `migrations/` folder (e.g., `install.sql`). The `PluginManager` can execute packaged SQL files upon activation. Alternatively, you can run DB setup manually inside the `activate()` method.

---

## 4. Coding Standards

When contributing to core or building plugins, adhere to these standards:

- **PSR-4 Autoloading:** Ensure new classes follow PSR-4 naming and directory conventions.
- **ORM:** Use Eloquent ORM for database access instead of raw queries whenever possible.
- **Templating:** Use Blade for all view rendering. Keep PHP logic out of templates.
- **Controllers:** Keep controllers thin. Push heavy business logic into models or specialized helper classes.

---

## 5. Security Practices

PankhCMS is heavily focused on security. Any code you write (core or plugin) must comply with these rules:

- **Password Storage:** Always use `password_hash()` for storing passwords. The system relies on strong hashing (BCrypt/Argon2).
- **File Uploads:** Never trust user uploads. Validate file uploads with MIME sniffing and extension mapping. Ensure uploaded files are not executable by the web server.
- **CSRF Protection:** Use CSRF tokens in all forms and AJAX requests. The core admin panel handles this automatically for standard routes.
- **Session Hardening:** Rely on the core `session_init()` helper to enforce secure session settings (e.g., `SameSite=Lax`, strict mode).
- **Rate Limiting:** Protect sensitive endpoints (like login forms) with rate limiting and exponential backoff mechanisms.
- **Input/Output:** Validate all incoming input. Escape output in Blade templates using `{{ $var }}` (which runs `htmlspecialchars`). Use `{!! $var !!}` ONLY when you are absolutely certain the HTML is safe and sanitized.

---

## 6. Testing & Troubleshooting

- **Linting:** Always lint your PHP files before committing:
  ```bash
  php -l path/to/file.php
  ```
- **Tests:** Add PHPUnit tests in `tests/` for core changes. Run them via `composer test`.
- **Cache Clearing:** Blade views are compiled and cached in `storage/cache/`. If changes aren't appearing, clear the cache:
  ```bash
  find storage/cache -type f -name '*.php' -delete
  ```
- **Permissions:** If you encounter errors relating to uploads, themes, or plugin removal, verify that your web user owns `storage/`, `public/uploads/`, and `themes/`.

---

## 7. Contributing

1. Fork the repository and create a feature branch.
2. Make your changes in a small, focused manner.
3. If adding a feature that modifies core schemas, add the necessary install SQL to `database/migrations`.
4. Run `php -l` and existing tests before submitting.
5. Submit a Pull Request with a clear description of the problem solved or feature added.

---

## 8. License

PankhCMS is released under the MIT License.
