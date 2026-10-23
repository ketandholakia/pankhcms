# PankhCMS

PankhCMS is a lightweight, simple, and extensible PHP Content Management System built with [FlightPHP](https://flightphp.com/) and [Jenssegers Blade](https://github.com/jenssegers/blade). It provides a fast and secure foundation for managing content with a flexible plugin architecture.

## 🚀 Features

- **Lightweight Core:** Built on top of FlightPHP for minimal overhead.
- **Blade Templating:** Utilizes a standalone version of Laravel's Blade engine.
- **Eloquent ORM:** Easy and powerful database interactions.
- **Extensible:** Robust plugin system with lifecycle management (install, boot, activate, uninstall) and hooks/events.
- **Admin UI:** Clean and intuitive administrative interface with built-in page builders and media management.

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Composer**: For dependency management
- **Database**: MySQL or SQLite
- **Web Server**: Apache, Nginx, or PHP's built-in server for development

## 🛠️ Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ketandholakia/pankhcms.git
   cd pankhcms
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   ```
   For production servers, skip development packages (phpunit etc.):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   > Note: `composer.json` pins `config.platform.php` to 8.2.0, so
   > dependency resolution targets PHP 8.2 even when you run composer
   > from a newer PHP.

3. **Configuration:**
   - Copy the environment example file:
     ```bash
     cp .env.example .env
     ```
   - Update `.env` with your database credentials and `APP_URL`.
   - Ensure your web server's document root points to the `public/` directory.

4. **Directory Permissions:**
   Ensure your web server has write access to the storage and upload directories:
   ```bash
   mkdir -p storage/cache storage/logs public/uploads
   chown -R $(whoami):www-data storage public/uploads
   chmod -R 775 storage public/uploads
   ```

5. **Database Migration:**
   Run the migrations to set up your database schema (works for both SQLite and MySQL):
   ```bash
   php scripts/migrate.php
   ```
   The web installer (`/install`) runs the same migrations for you.

6. **Start the Development Server:**
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   Access the site at `http://127.0.0.1:8000` and the admin panel at `/admin`.

## ⬆️ Upgrading an Existing Installation

1. **Back up first.** Create a database backup in *Admin > Backups* (or via the CLI) and keep a copy of your `.env` and any custom theme files.
2. Replace the application files (keep `.env`, `storage/`, `public/uploads/`, `themes/`, and `plugins/`).
3. Run the migrations — they are idempotent and bring the schema and default roles/permissions up to date:
   ```bash
   php scripts/migrate.php
   ```
4. Clear the caches if views behave oddly:
   ```bash
   find storage/cache -type f ! -name '.gitignore' -delete
   ```
5. Log in and confirm the site and admin panel work. Never restore a backup into a newer schema without running `php scripts/migrate.php` afterwards.

## 💾 Backups & Recovery

- **Create:** *Admin > Backups* (full = database + uploads + themes; database-only) or `Admin\Core\BackupManager` via code. Backups are stored in `storage/backups/` as ZIP archives.
- **Restore:** *Admin > Backups > Restore* restores the **database only** (uploads/themes are untouched) after explicit confirmation. A **recovery backup of the live database is taken automatically first**; if the restore fails, the database is returned to its pre-restore state. If the recovery itself fails, maintenance mode is switched on and `storage/backups/RESTORE-FAILED-*.txt` contains step-by-step operator instructions.
- **Retention:** backups are kept until deleted in the admin UI — no automatic pruning. Delete old archives regularly, and exclude `storage/backups/` from your own backup jobs to avoid recursion.
- **Analytics pruning:** `php scripts/prune-page-views.php` deletes page-view tracking rows older than 90 days (`--days=N` to override). Schedule it daily via cron.

## 🚀 Deployment Checklist

- **PHP 8.2+** with `pdo_sqlite` *or* `pdo_mysql`, `zip`, `fileinfo`, `mbstring`, `dom`, `openssl`; the web installer verifies all of these.
- Run `composer install --no-dev --optimize-autoloader` on production.
- `.env`: set `APP_DEBUG=false`, a correct `APP_URL`, and database credentials. Set `TRUSTED_PROXIES` (comma-separated IPs/CIDRs) only if PHP sits behind a reverse proxy — otherwise leave it empty. Behind that proxy, also set `TRUSTED_PROXY_HEADER` to the single header the proxy actually provides (`X-Forwarded-For` (default), `X-Real-IP`, or `CF-Connecting-IP` for Cloudflare); only that one header is trusted for client-IP resolution. Multi-host installs should list every public hostname in `ALLOWED_HOSTS`; requests with any other `Host` header bypass the full-page cache entirely.
- **Outgoing mail** (password resets, contact notifications): set `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` (`tls` default; `ssl`/`none` supported) and `MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME` to send over SMTP (PHPMailer). Leave `MAIL_HOST` empty only for local-delivery setups using PHP's `mail()`; failures are logged to `storage/logs/mailer.log`.
- The `timezone` setting (Admin > Settings) is applied to PHP on every request and CLI job; publish dates entered in the admin are interpreted in that timezone and invalid dates are rejected.
- Contact messages store the **anonymized** visitor IP by default; set `CONTACT_STORE_FULL_IP=true` only if the inbox genuinely needs full addresses.
- Web-server-writable directories: `storage/` (cache, logs, backups) and `public/uploads/`.
- Ensure `public/install/lock` exists after installation (the installer creates it) so the installer cannot be re-run.
- Point the web server's document root at `public/`. Subdirectory deployments are supported — set `APP_URL` including the subdirectory path.
- Error pages show a generic 500 in production; full exception details are written to `storage/logs/` (and only rendered when `APP_DEBUG=true`).
- There are **no default admin credentials** — the administrator account is created by you during installation.

## 🔒 Security

PankhCMS is built with security in mind, incorporating several protections:
- **CSRF Protection:** All admin POST routes, including AJAX and uploads, are protected.
- **Session Hardening:** Secure cookie settings, strict mode, and `SameSite=Lax`.
- **Upload Hardening:** Server-side MIME sniffing, size limits, and safe filenames/extensions.
- **Login Rate Limiting:** Exponential backoff after 5 failed attempts (IP + username).
- **Password Policy:** Minimum 10 characters, requiring uppercase, lowercase, number, and symbol. Stored securely with `password_hash()` (BCrypt/Argon2) and automatic rehash on login.

## 📚 Documentation

For more detailed information on working with PankhCMS, please refer to the specific manuals:

- [**Developer Manual**](DEVELOPER_MANUAL.md): Comprehensive guide on application architecture, core APIs, contributing, and building plugins.
- [**Theme Developer Manual**](THEME_DEVELOPER_MANUAL.md): Guide for creating and customizing frontend themes and blocks.

## 📄 License

PankhCMS is open-sourced software licensed under the [MIT license](LICENSE).
