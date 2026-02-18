# PankhCMS

A lightweight Content Management System built on top of the [Flight PHP Framework](https://flightphp.com/).

## 🚀 About

PankhCMS aims to provide a fast, simple, and extensible platform for managing content. Leveraging the speed and simplicity of FlightPHP, it offers a minimal footprint with maximum flexibility.

## 📋 Requirements

- **PHP**: 7.4 or higher
- **Composer**: For dependency management
- **Web Server**: Apache, Nginx, or PHP's built-in server (Laragon recommended for Windows)

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
   - Copy the example configuration file (if available) or set up your `config.php`.
   - Ensure your web server points to the project root (or `public/` folder if configured that way).

### Installer flow (`/install`)

- The application front controller (`public/index.php`) now only bootstraps the app.
- If the app is not configured yet, visiting `/` redirects to `/install`.
- The setup wizard lives in:
   - `public/install/index.php`
   - `public/install/complete.php`
- The installer creates an `install/lock` file after successful setup.
- If either `.env` exists or `install/lock` exists, the installer is blocked with HTTP 403.

#### Reinstall (development only)

To run the installer again, remove both:

- `.env`
- `public/install/lock`

Then open `/install` again.

## 💻 Usage

### Using PHP Built-in Server
You can quickly start the application using the built-in PHP server:

```bash
# If index.php is in the root
php -S localhost:8000

# If index.php is in a public directory
php -S localhost:8000 -t public
```

### Using Laragon
Since this project is set up in a Laragon environment:
1. Start Laragon.
2. The app should be automatically accessible at `http://PankhCMS.test` (assuming auto-virtual hosts are enabled).

## 🩺 Troubleshooting

### `/install` returns `403 Installer is locked`

The installer is intentionally blocked when setup is already completed.

- Check whether `.env` exists.
- Check whether `public/install/lock` exists.
- For development-only reinstall, remove both files and open `/install` again.

### `Failed opening required .../vendor/autoload.php`

- Run `composer install` in the project root.
- Ensure your web server document root points to `public/` (or `htdocs/` in that setup).
- Confirm the front controller uses project root as `dirname(__DIR__)`.

### Logged out but `/admin` still opens

- Make sure `app/Middleware/AdminMiddleware.php` uses `before()` middleware method.
- Verify logout route is `POST /admin/logout`.
- Clear browser cookies for the site if an old session remains.

## 📂 Project Structure

* `app/` - Contains application logic (routes, controllers, models).
* `views/` - HTML templates and views.
* `vendor/` - Third-party libraries (FlightPHP, etc.).
* `public/` - Web server entry point (assets, index.php).

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the MIT license.