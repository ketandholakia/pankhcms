# PankhCMS Developer Manual

## 1. Introduction

This document provides information for developers working on the PankhCMS project. It covers the project's architecture, key components, development environment setup, and coding conventions.


### 1.1. Project Overview

PankhCMS is a modular, PHP-based content management system designed for flexibility, speed, and extensibility. It enables rapid development of websites with custom themes, user roles, and dynamic content. The system is built on FlightPHP, uses Eloquent ORM for database access, and Blade for templating.

*   **Framework:** [FlightPHP](https://flightphp.com/)
*   **Database:** SQLite (default), MySQL supported
*   **Templating Engine:** Blade (`jenssegers/blade`)
*   **Key Priorities:**
    *   Security: Yes (input validation, middleware, role-based access)
    *   Performance: Yes (caching, optimized queries)
*   **API:** The project does not expose a public API by default, but can be extended.
*   **Team Size:** 1+ (open to contributors)

---

## 2. Getting Started

### 2.1. Prerequisites

*   PHP (see `composer.json` for version requirements)
*   Composer

*   SQLite or MySQL database
*   Local web server (Apache, Nginx, or PHP built-in server)

### 2.2. Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd PankhCMS
    ```

2.  **Install dependencies:**
    This command will install all the required PHP packages from `composer.json`.
    ```bash
    composer install
    ```


3.  **Environment Configuration:**
    *   The project configuration is managed in `config/app.php` and `config/database.php`. Set your database credentials and environment-specific settings here. You may use a `.env` file if supported.


4.  **Database Migration:**
    The project uses `illuminate/database` (Eloquent). Run the migration scripts in `database/sqlite-php-scripts/` to set up the database schema:
    ```bash
    php database/sqlite-php-scripts/create-tables.php
    php database/sqlite-php-scripts/seed.php
    ```

5.  **Start the development server:**

    *   **Using Docker (if configured):**
        ```bash
        docker-compose up -d
        ```
    *   **Using PHP's built-in server:**
        ```bash
        php -S localhost:8000 -t public
        ```
    *   **With Apache/Nginx:** Point your web root to the `public/` directory.

## 3. Architecture

PankhCMS is built on the FlightPHP micro-framework. It follows a simple and flexible architecture, promoting a clean separation of concerns.


### 3.1. Directory Structure

```
/PankhCMS
├── app/
│   ├── Controllers/    # Route handlers (Auth, Contact, Admin, Site, etc.)
│   ├── Core/           # Core classes (Auth, Bootstrap, Theme)
│   ├── Helpers/        # Utility functions (breadcrumbs, menu, etc.)
│   ├── Middleware/     # Request filters (AdminMiddleware, etc.)
│   ├── Models/         # Eloquent models (User, Page, Menu, etc.)
├── config/             # Configuration files (app.php, database.php)
├── database/
│   └── sqlite-php-scripts/ # Migration and seed scripts
├── public/
│   ├── index.php       # Application entry point
│   └── install/        # Installer scripts
├── routes/             # Route definitions (web.php, admin.php)
├── storage/            # Cache and logs
├── themes/             # Theme packages (bootstrap5, bulma, etc.)
├── vendor/             # Composer dependencies
├── views/              # Blade templates
└── composer.json
```


### 3.2. Request Lifecycle

1.  All HTTP requests are directed to `public/index.php`.
2.  Composer autoloader and helper files (`app/Helpers/functions.php`) are loaded.
3.  A session is started.
4.  `App\Core\Bootstrap::init()` initializes environment, config, database, and view engine.
5.  Routes from `routes/web.php` and `routes/admin.php` are loaded and registered.
6.  `Flight::start()` dispatches the router.
7.  FlightPHP matches the request URI to a route and executes its callback (usually a controller method).
8.  The controller processes the request, interacts with models, and renders a Blade template or returns JSON.


## 4. Key Components & Libraries

*   **FlightPHP (`flightphp/core`):** Routing and request/response cycle.
*   **Illuminate Database (`illuminate/database`):** Eloquent ORM for database access. Models extend `Illuminate\Database\Eloquent\Model`.
*   **Blade (`jenssegers/blade`):** Templating engine for views.
*   **PHP DotEnv (`vlucas/phpdotenv`):** Loads environment variables from `.env`.
*   **Carbon (`nesbot/carbon`):** Date/time API for PHP.


## 5. Application Core Functions

### 5.1. Bootstrapping (`App\Core\Bootstrap`)

The `App\Core\Bootstrap` class is the heart of the application's startup process. Its static `init()` method is called from `public/index.php` immediately after dependencies are loaded.

**Key Responsibilities:**

*   **Environment Loading:** Initializes `vlucas/phpdotenv` to load the `.env` file into the environment.
*   **Configuration:** Loads configuration files (e.g., from `config/`) and makes them available throughout the application.
*   **Database Connection:** Sets up the `illuminate/database` capsule manager. It reads database credentials from the environment/config and establishes the connection, making the Eloquent ORM and Query Builder available (e.g., via a `Flight::db()` helper).
*   **View Engine Registration:** Initializes the Blade templating engine (`jenssegers/blade`). It configures the path to the `views` and `cache` directories and registers a `view` method with Flight so controllers can easily render templates.
*   **Dependency Injection:** May set up other services or bindings in Flight's dependency injection container.


### 5.2. Routing (`routes/web.php`, `routes/admin.php`)

All web-accessible URLs are defined in these files. They map HTTP methods and URI patterns to controller actions.

**Example:**
```php
// Public route
Flight::route('GET /contact', ['App\Controllers\ContactController', 'show']);
// Admin route
Flight::route('GET /admin', ['App\Controllers\Admin\DashboardController', 'index'], ['App\Middleware\AdminMiddleware']);
```

**Function:** The router directs incoming traffic to the correct controller. Add new pages or endpoints by updating these files.


### 5.3. Controllers (`app/Controllers/`)

Controllers handle requests, business logic, and responses.

**Core Functions:**
*   Receive request data via `Flight::request()`
*   Interact with models (Eloquent)
*   Apply business logic and validation
*   Render Blade templates or return JSON


### 5.4. Helper Functions (`app/Helpers/functions.php`)

Globally available functions for formatting, URLs, config access, etc. Loaded in `public/index.php`. Call directly as needed.


## 6. Models & Database

Database interactions use Eloquent ORM. Models are in `app/Models/` (e.g., `User.php`, `Page.php`).

### 6.1. Models
Each model class maps to a database table. Extend `Illuminate\Database\Eloquent\Model`.

### 6.2. Migrations
Run scripts in `database/sqlite-php-scripts/` to manage schema. Keep schema versioned and up to date.

### 6.3. Transactions
Use transactions for atomic operations:
```php
Flight::db()->transaction(function () {
    // ... multiple database operations
});
```


---

## 7. Views & Theming

*   Views are Blade templates in `views/`
*   Themes are in `themes/` (e.g., `bootstrap5`, `bulma`)
*   Switch themes via `config/app.php` or admin panel

---

## 8. Helper Functions & Utilities

*   Helpers in `app/Helpers/` (autoloaded or included)
*   Use for menus, breadcrumbs, formatting, etc.

---

## 9. Middleware & Security

*   Middleware in `app/Middleware/` (e.g., `AdminMiddleware.php`)
*   Attach to routes for authentication/authorization
*   Use HTTPS, validate input, sanitize output

---

## 10. Testing & Debugging

*   Manual and automated testing (PHPUnit recommended)
*   Enable debug in `config/app.php`
*   Logs in `storage/`

---

## 11. Contribution & Coding Standards

*   **Style:** Follow **PSR-12**. Use `php-cs-fixer` or `phpcs` to enforce.
*   **PHPDoc:** Document all classes, methods, and properties.
*   **Type Safety:** Use `declare(strict_types=1);` and type hints.
*   **PRs:** Fork, branch, and submit pull requests.
*   **Issues:** Report bugs and feature requests via issue tracker.


## 12. Tooling

*   **AI-Assisted Development:**
    *   Run `php runway ai:generate-instructions` to generate AI-specific instructions for your IDE.
*   **Composer:** Manage dependencies with Composer.
*   **PHPUnit:** For automated testing.

---
