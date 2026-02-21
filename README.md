
# PankhCMS

A lightweight Content Management System built on top of the [Flight PHP Framework](https://flightphp.com/).

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