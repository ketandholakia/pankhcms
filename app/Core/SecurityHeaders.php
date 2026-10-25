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
     * The HSTS policy: env-configurable so operators can opt out of
     * includeSubDomains (HSTS is domain-wide, including sibling
     * subdomains — enabling it here forces HTTPS on EVERY subdomain for
     * the max-age duration once a visitor's browser sees the header, so
     * a sibling service without HTTPS breaks). Defaults preserve the
     * previous behavior: one year with includeSubDomains. Setting
     * HSTS_MAX_AGE=0 disables the header entirely (the RFC-correct
     * opt-out).
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
            $hsts = self::hstsValue();
            if ($hsts !== null) {
                $headers['Strict-Transport-Security'] = $hsts;
            }
        }

        return $headers;
    }

    /**
     * The Strict-Transport-Security header value, or null when HSTS is
     * disabled (max-age 0).
     */
    public static function hstsValue(): ?string
    {
        $maxAge = (int) env('HSTS_MAX_AGE', 31536000);
        if ($maxAge <= 0) {
            return null;
        }

        $includeSubDomains = filter_var(
            (string) env('HSTS_INCLUDE_SUBDOMAINS', 'true'),
            FILTER_VALIDATE_BOOL
        );

        return 'max-age=' . $maxAge . ($includeSubDomains ? '; includeSubDomains' : '');
    }
}
