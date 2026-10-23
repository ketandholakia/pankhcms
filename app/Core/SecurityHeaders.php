<?php

namespace App\Core;

/**
 * Site-wide security headers shared by every response path.
 *
 * public/index.php registers these through Flight's before('start')
 * hook, but the anonymous page cache serves requests BEFORE the
 * framework starts — PageCache::serveEarlyIfPossible() must send the
 * same headers itself, so the logic lives here and is called from both
 * places. The admin CSP stays in public/index.php: /admin is excluded
 * from the page cache, so it only ever ships through the normal path.
 */
class SecurityHeaders
{
    /**
     * Send the site-wide security headers for the current request.
     */
    public static function send(): void
    {
        foreach (self::headers() as $name => $value) {
            header($name . ': ' . $value);
        }
    }

    /**
     * The headers that apply to every response. HSTS is only sent over
     * HTTPS (sending it over plain HTTP is ignored at best and captured
     * by intermediaries at worst).
     *
     * @return array<string, string>
     */
    public static function headers(): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        if ($isHttps) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }
}
