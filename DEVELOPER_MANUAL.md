# PankhCMS Developer Manual

## 1. Introduction

PankhCMS is a modular, PHP-based content management system designed for flexibility, speed, and extensibility. It uses FlightPHP, Eloquent ORM, and Blade for templating.

## 2. Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- SQLite or MySQL database
- Local web server (Apache, Nginx, or PHP built-in server)

### Installation
1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd PankhCMS
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Configure `.env` and `config/app.php`, `config/database.php`.
4. Run migration scripts:
   ```bash
   php database/sqlite-php-scripts/create-tables.php
   php database/sqlite-php-scripts/seed.php
   ```
5. Start the server:
   ```bash
   php -S localhost:8000 -t public
   ```

## 3. Architecture & Structure

- `app/` - Controllers, models, helpers
- `views/` - Blade templates
- `public/` - Entry point, assets
- `database/` - Migration/seed scripts
- `storage/` - Cache, logs, uploads
- `config/` - App and DB config
- `vendor/` - Composer dependencies

## 4. Coding Standards

- Use PSR-4 autoloading for new classes
- Use Eloquent ORM for DB access
- Use Blade for templating
- Keep controllers thin; use helpers/models for business logic
- Validate input, escape output, use prepared statements

## 5. Security Checklist

- Use `password_hash()` for storing passwords
- Validate file uploads with MIME sniffing and extension mapping
- Use CSRF tokens in all forms and AJAX requests
- Harden session settings (`session_init()` in helpers)
- Use rate limiting for login and sensitive endpoints

## 6. Testing

- Use `php -l` for syntax checks
- Add PHPUnit tests in `tests/` (if available)
- Run `composer test` to execute tests
- Test new features and bug fixes before submitting PRs

## 7. Deployment

- Deploy to any PHP-compatible server (Apache, Nginx, etc.)
- Point web root to `public/`
- Set up `.env` and config files for production
- Use HTTPS and secure session settings in production
- Run migrations and seed scripts as needed

## 8. API Extension

- Add new routes in `routes/web.php` or `routes/admin.php`
- Create controllers in `app/Controllers/`
- Use Eloquent models for data access
- Return JSON responses for API endpoints
- Secure API endpoints with middleware and CSRF/auth checks

## 9. Contributing

- Fork the repo and create a feature branch
- Make your changes and add tests if possible
- Run `php -l` and `composer test` (if tests exist) before submitting
- Submit a Pull Request with a clear description

## 10. Troubleshooting

- Installer locked: Remove `.env` and `public/install/lock` to rerun installer
- Session issues: Clear cookies and ensure secure settings
- Login issues: Wait for rate limit or reset password with strong `ADMIN_PASSWORD`

## 11. License

MIT License
