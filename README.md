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
   Run the migration scripts to set up your database schema. If using SQLite, run:
   ```bash
   php database/sqlite-php-scripts/create-tables.php
   php database/sqlite-php-scripts/seed.php
   ```
   *Alternatively, you can use `php scripts/run_migrations.php`.*

6. **Start the Development Server:**
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   Access the site at `http://127.0.0.1:8000` and the admin panel at `/admin`.

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
