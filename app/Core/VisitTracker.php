<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class VisitTracker
{
    public static function track(?int $pageId, string $path): void
    {
        try {
            $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            $source = self::detectSource($referrer);
            $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

            $sessionId = '';
            if (session_status() === PHP_SESSION_ACTIVE) {
                $sessionId = session_id();
            } else {
                // Only read the session when a cookie is present; anonymous
                // visitors without a session keep responses cacheable.
                if (isset($_COOKIE[session_name()])) {
                    session_init();
                    $sessionId = session_id();
                }
            }

            DB::table('page_views')->insert([
                'page_id' => $pageId,
                'path' => $path,
                'source' => $source,
                'referrer' => $referrer !== '' ? $referrer : null,
                'ip' => self::anonymizeIp(client_ip()),
                'user_agent' => $userAgent,
                'session_id' => $sessionId !== '' ? $sessionId : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Keep public page rendering resilient.
        }
    }

    /**
     * Handle a POST /_v view beacon. The page cache serves anonymous
     * hits before the framework boots, so view counting cannot happen
     * in the page render path; instead the layout ships a tiny
     * sendBeacon() script and this handler records the view. Kept
     * cheap on purpose: DB-only bootstrap (no plugins, no Blade), one
     * slug lookup + one insert, browsers-only (bots skipped), and
     * every failure swallowed — analytics must never break anything.
     * The visitor has no session cookie on cached hits, so no session
     * is started and the row carries no session id; the IP is stored
     * anonymized by track().
     */
    public static function handleBeacon(): void
    {
        // sendBeacon posts urlencoded data; accept a JSON body too.
        $path = trim((string) ($_POST['p'] ?? ''));
        if ($path === '' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $path = trim((string) ($decoded['p'] ?? ''));
        }

        // Cap and sanity-check: a path is at most one URL path.
        if ($path === '' || strlen($path) > 300 || !str_starts_with($path, '/')) {
            return;
        }

        $userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (self::isBot($userAgent)) {
            return;
        }

        try {
            require_once dirname(__DIR__, 2) . '/app/database.php';

            $slug = ltrim($path, '/');
            $pageId = \Illuminate\Database\Capsule\Manager::table('pages')
                ->where('slug', $slug)
                ->value('id');

            self::track($pageId !== null ? (int) $pageId : null, $path);
        } catch (\Throwable $e) {
            // Best effort only.
        }
    }

    /**
     * Conservative crawler detector for view counting (empty agents are
     * treated as bots: no real browser sends no User-Agent).
     */
    public static function isBot(string $userAgentLower): bool
    {
        if (trim($userAgentLower) === '') {
            return true;
        }
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|curl|wget|facebookexternalhit|headless|lighthouse|monitor|pingdom|uptime/',
            $userAgentLower
        );
    }
    /**
     * Truncate the last octet (IPv4) or the interface identifier (IPv6)
     * before storing, so a full IP address is never persisted.
     */
    public static function anonymizeIp(string $ip): string
    {
        $ip = trim($ip);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = 'x';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $bin = @inet_pton($ip);
            if ($bin === false || strlen($bin) !== 16) {
                return '';
            }
            // Keep the /64 routing prefix, zero the interface identifier.
            return (string) inet_ntop(substr($bin, 0, 8) . str_repeat("\x00", 8));
        }

        return '';
    }

    private static function detectSource(string $referrer): string
    {
        if ($referrer === '') {
            return 'direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (!$host) {
            return 'referral';
        }

        $host = strtolower((string) $host);

        if (str_contains($host, 'google.')) {
            return 'google';
        }
        if (str_contains($host, 'bing.')) {
            return 'bing';
        }
        if (str_contains($host, 'yahoo.')) {
            return 'yahoo';
        }
        if (str_contains($host, 'duckduckgo.')) {
            return 'duckduckgo';
        }
        if (str_contains($host, 'facebook.')) {
            return 'facebook';
        }
        if (str_contains($host, 'instagram.')) {
            return 'instagram';
        }
        if (str_contains($host, 'x.com') || str_contains($host, 'twitter.')) {
            return 'x/twitter';
        }
        if (str_contains($host, 'linkedin.')) {
            return 'linkedin';
        }

        return 'referral';
    }
}
