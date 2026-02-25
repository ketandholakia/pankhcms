<?php
if (!function_exists('build_menu_tree')) {
    function build_menu_tree($items, $parent = null)
    {
        $branch = [];
        foreach ($items as $item) {
            if ($item->parent_id == $parent) {
                $children = build_menu_tree($items, $item->id);
                if ($children) {
                    $item->children = $children;
                }
                $branch[] = $item;
            }
        }
        return $branch;
    }
}

use App\Models\Page;

if (!function_exists("env")) {
    function env($key, $default = null)
    {
        $value = null;

        if (is_string($key)) {
            if (array_key_exists($key, $_ENV)) {
                $value = $_ENV[$key];
            } elseif (array_key_exists($key, $_SERVER)) {
                $value = $_SERVER[$key];
            } else {
                $envValue = getenv($key);
                if ($envValue !== false) {
                    $value = $envValue;
                }
            }
        }

        if ($value === null) {
            return $default;
        }

        switch (strtolower($value)) {
            case "true":
            case "(true)":
                return true;
            case "false":
            case "(false)":
                return false;
            case "empty":
            case "(empty)":
                return "";
            case "null":
            case "(null)":
                return;
        }

        if (
            defined("STDIN") &&
            strlen($value) > 1 &&
            $value[0] === '"' &&
            $value[strlen($value) - 1] === '"'
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists("generate_slug")) {
    function generate_slug($text)
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Replace non letters/digits with hyphen
        $text = preg_replace("/[^a-z0-9]+/", "-", $text);

        // Remove duplicate hyphens
        $text = trim($text, "-");

        return $text ?: "page";
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $trustProxy = (bool) env('TRUST_PROXY', false);
        if ($trustProxy) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if ($forwarded) {
                $parts = array_map('trim', explode(',', $forwarded));
                if (!empty($parts[0])) {
                    return $parts[0];
                }
            }

            $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? '';
            if ($realIp) {
                return $realIp;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('login_throttle_key')) {
    function login_throttle_key(string $identifier, string $ip): string
    {
        $identifier = strtolower(trim($identifier));
        $ip = trim($ip);
        return hash('sha256', $identifier . '|' . $ip);
    }
}

if (!function_exists('login_throttle_read')) {
    function login_throttle_read(string $key): array
    {
        $path = dirname(__DIR__, 2) . '/storage/cache/login_throttle/';
        $file = $path . $key . '.json';
        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('login_throttle_write')) {
    function login_throttle_write(string $key, array $data): void
    {
        $path = dirname(__DIR__, 2) . '/storage/cache/login_throttle/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $file = $path . $key . '.json';
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return;
        }

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
        }

        fclose($fp);
    }
}

if (!function_exists('login_throttle_clear')) {
    function login_throttle_clear(string $key): void
    {
        $file = dirname(__DIR__, 2) . '/storage/cache/login_throttle/' . $key . '.json';
        if (is_file($file)) {
            unlink($file);
        }
    }
}

if (!function_exists('login_throttle_check')) {
    function login_throttle_check(string $key): array
    {
        $now = time();
        $data = login_throttle_read($key);
        $lockUntil = (int) ($data['lock_until'] ?? 0);

        if ($lockUntil > $now) {
            return ['allowed' => false, 'retry_after' => $lockUntil - $now];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }
}

if (!function_exists('login_throttle_register_failure')) {
    function login_throttle_register_failure(string $key): array
    {
        $now = time();
        $window = 15 * 60; // 15 minutes

        $data = login_throttle_read($key);
        $first = (int) ($data['first'] ?? $now);
        $count = (int) ($data['count'] ?? 0);

        if ($now - $first > $window) {
            $first = $now;
            $count = 0;
        }

        $count++;

        $lockUntil = 0;
        if ($count > 5) {
            $delay = (int) min(1800, pow(2, $count - 5));
            $lockUntil = $now + $delay;
        }

        $data = [
            'first' => $first,
            'count' => $count,
            'lock_until' => $lockUntil,
            'last' => $now,
        ];

        login_throttle_write($key, $data);

        return ['locked' => $lockUntil > $now, 'retry_after' => max(0, $lockUntil - $now)];
    }
}

if (!function_exists('password_policy_errors')) {
    function password_policy_errors(string $password): array
    {
        $errors = [];
        if (strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must include a lowercase letter.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must include an uppercase letter.';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must include a number.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must include a symbol.';
        }

        return $errors;
    }
}

if (!function_exists('session_init')) {
    function session_init(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $appUrl = (string) env('APP_URL', '');
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || str_starts_with($appUrl, 'https://');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', $isSecure ? '1' : '0');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        session_init();

        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool
    {
        session_init();

        $expected = $_SESSION['_csrf_token'] ?? '';
        if (!is_string($expected) || $expected === '') {
            return false;
        }

        $provided = $_POST['_csrf']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'.''] ?? null);

        if (!is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}

if (!function_exists('csrf_require')) {
    function csrf_require(): void
    {
        if (csrf_verify()) {
            return;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isJson = str_contains($accept, 'application/json')
            || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        if (class_exists('Flight')) {
            if ($isJson) {
                \Flight::response()->status(403);
                \Flight::json(['error' => 'CSRF token mismatch'], 403);
                exit;
            }

            \Flight::halt(403, 'CSRF token mismatch');
        }

        http_response_code(403);
        echo 'CSRF token mismatch';
        exit;
    }
}

if (!function_exists("unique_slug")) {
    function unique_slug($title, $id = null)
    {
        $slug = generate_slug($title);
        $original = $slug;
        $i = 1;

        while (true) {
            $query = Page::where("slug", $slug);

            // Ignore current page when editing
            if ($id) {
                $query->where("id", "!=", $id);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $original . "-" . $i++;
        }
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $settings = [];

        if (empty($settings)) {
            try {
                if (\Illuminate\Database\Capsule\Manager::schema()->hasTable('settings')) {
                    $allSettings = \Illuminate\Database\Capsule\Manager::table('settings')->pluck('value', 'key')->toArray();
                    $settings = $allSettings;
                }
            } catch (\Throwable $e) { /* Settings table not ready */ }
        }

        return $settings[$key] ?? $default;
    }
}

if (!function_exists('theme_asset')) {
    function theme_asset(string $file): string
    {
        return \App\Core\Theme::asset($file);
    }
}

if (!function_exists('theme_view')) {
    /**
     * Resolve the first existing view from a list, with theme->default fallback.
     *
     * @param string $view Primary view name
     * @param string[] $fallbacks Additional view names
     */
    function theme_view(string $view, array $fallbacks = []): string
    {
        $blade = \Flight::get('blade');
        $candidates = array_merge([$view], $fallbacks);
        foreach ($candidates as $candidate) {
            if ($blade && method_exists($blade, 'exists') && $blade->exists($candidate)) {
                return $candidate;
            }
        }
        return $view;
    }
}

if (!function_exists('render_block')) {
    /**
     * Render a theme block by type.
     *
     * Usage: {!! render_block($block['type'], $block['settings'] ?? []) !!}
     */
    function render_block(string $type, $data = []): string
    {
        $type = trim($type);
        if ($type === '') {
            return '';
        }

        $view = theme_view('blocks.' . $type);

        $vars = [];
        if (is_array($data)) {
            // If associative, pass keys as variables; otherwise expose as $data
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            $vars = $isAssoc ? $data : ['data' => $data];
        } else {
            $vars = ['data' => $data];
        }

        try {
            return (string) \Flight::get('blade')->render($view, $vars);
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('seo_setting')) {
    function seo_setting(string $primaryKey, ?string $legacyKey = null, $default = null)
    {
        $value = setting($primaryKey, null);
        if ($value !== null && $value !== '') {
            return $value;
        }

        if ($legacyKey) {
            $legacy = setting($legacyKey, null);
            if ($legacy !== null && $legacy !== '') {
                return $legacy;
            }
        }

        return $default;
    }
}

if (!function_exists('seo_site_title')) {
    function seo_site_title(): string
    {
        return (string) seo_setting('site_title', 'site_name', env('APP_NAME', 'PankhCMS'));
    }
}

if (!function_exists('seo_site_url')) {
    function seo_site_url(): string
    {
        $url = (string) seo_setting('site_url', 'canonical_base', env('APP_URL', ''));
        return rtrim($url, '/');
    }
}

if (!function_exists('seo_absolute_url')) {
    function seo_absolute_url(?string $pathOrUrl): string
    {
        $value = trim((string) $pathOrUrl);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $base = seo_site_url();
        if ($base === '') {
            return $value;
        }

        return $base . '/' . ltrim($value, '/');
    }
}

if (!function_exists('seo_title')) {
    function seo_title($page = null): string
    {
        $seoTitle = trim((string) ($page->seo_title ?? $page->meta_title ?? ''));
        if ($seoTitle !== '') {
            return $seoTitle;
        }

        $pageTitle = trim((string) ($page->title ?? ''));
        $siteTitle = seo_site_title();

        if ($pageTitle === '') {
            return $siteTitle;
        }

        return $siteTitle !== '' ? $pageTitle . ' | ' . $siteTitle : $pageTitle;
    }
}

if (!function_exists('seo_description')) {
    function seo_description($page = null): string
    {
        $pageDescription = trim((string) ($page->seo_description ?? $page->meta_description ?? ''));
        if ($pageDescription !== '') {
            return $pageDescription;
        }

        $excerpt = trim((string) ($page->excerpt ?? $page->summary ?? ''));
        if ($excerpt !== '') {
            return $excerpt;
        }

        return (string) seo_setting('default_meta_description', 'seo_default_description', setting('site_tagline', ''));
    }
}

if (!function_exists('seo_keywords')) {
    function seo_keywords($page = null): string
    {
        $pageKeywords = trim((string) ($page->seo_keywords ?? $page->meta_keywords ?? ''));
        if ($pageKeywords !== '') {
            return $pageKeywords;
        }

        return (string) seo_setting('default_meta_keywords', 'seo_default_keywords', '');
    }
}

if (!function_exists('canonical_url')) {
    function canonical_url($page = null): string
    {
        $pageCanonical = trim((string) ($page->canonical_url ?? ''));
        if ($pageCanonical !== '') {
            return seo_absolute_url($pageCanonical);
        }

        $slug = trim((string) ($page->slug ?? ''));
        if ($slug !== '') {
            return seo_absolute_url('/' . ltrim($slug, '/'));
        }

        $requestPath = parse_url(\Flight::request()->url ?? '/', PHP_URL_PATH) ?: '/';
        return seo_absolute_url($requestPath);
    }
}

if (!function_exists('seo_robots')) {
    function seo_robots($page = null): string
    {
        $robots = trim((string) ($page->robots ?? ''));
        if ($robots !== '') {
            return $robots;
        }

        return (string) seo_setting('robots_default', null, 'index, follow');
    }
}

if (!function_exists('seo_image')) {
    function seo_image($page = null): string
    {
        $image = trim((string) (
            $page->seo_image
            ?? $page->og_image
            ?? $page->featured_image
            ?? seo_setting('og_image', 'og_image_default', '')
        ));

        return seo_absolute_url($image);
    }
}

if (!function_exists('seo_twitter_card')) {
    function seo_twitter_card($page = null): string
    {
        $card = trim((string) ($page->twitter_card ?? ''));
        if ($card !== '') {
            return $card;
        }

        return (string) seo_setting('twitter_card', null, 'summary_large_image');
    }
}

if (!function_exists('seo_image_alt')) {
    function seo_image_alt($page = null, ?string $alt = null): string
    {
        $candidate = trim((string) ($alt ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }

        return trim((string) ($page->title ?? seo_site_title()));
    }
}
