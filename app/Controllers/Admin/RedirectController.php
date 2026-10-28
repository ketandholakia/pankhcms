<?php

namespace App\Controllers\Admin;

use App\Models\Redirect;
use Flight;

class RedirectController
{
    /** Paths the redirect system must never shadow (segment-exact). */
    private const RESERVED_PREFIXES = ['/admin', '/install', '/api', '/_v', '/themes', '/storage', '/assets', '/uploads'];

    /**
     * Validate a redirect pair. Returns the normalized [from, to] on
     * success or an error string.
     *
     * Rules (threat model):
     * - "from" must be a same-site path: a single path with no control
     *   characters, not segment-colliding with an internal route
     *   (/admin, /install, /api, /_v, ...) — segment-exact, so
     *   "/administrator" stays legal while "/admin/x" is rejected.
     * - "to" must be either a same-site path (the common case) or an
     *   absolute URL pointing at this site (https/http only);
     *   protocol-relative targets (//evil.com), other schemes
     *   (javascript:, data:, vbscript:) and control characters (CRLF
     *   header injection, whitespace-obfuscated hosts) are rejected.
     * - No self-redirects (from === to after normalization).
     * - Multi-step cycles are rejected by walking existing redirects.
     *
     * @return array{0: string, 1: string}|string normalized [from, to] or an error message
     */
    public static function validatePair(string $rawFrom, string $rawTo, ?int $ignoreId = null)
    {
        // Validate the RAW inputs first: empty values and control
        // characters are rejected outright (never silently stripped), so
        // CRLF header injection cannot launder through normalization.
        if (trim($rawFrom) === '' || trim($rawTo) === '') {
            return 'Both the from-path and the target URL are required.';
        }

        if (preg_match('/[\x00-\x1f\x7f]/', $rawFrom) || preg_match('/[\x00-\x1f\x7f]/', $rawTo)) {
            return 'Redirects may not contain control characters.';
        }

        $from = self::normalizeFrom($rawFrom);
        $to = self::normalizeTo($rawTo);

        foreach (self::RESERVED_PREFIXES as $reserved) {
            if ($from === $reserved || $from === $reserved . '/' || str_starts_with($from, $reserved . '/')) {
                return "The from-path collides with the internal {$reserved} route.";
            }
        }

        if ($to === $from) {
            return 'A redirect cannot point at itself.';
        }

        if (!self::isAllowedTo($to)) {
            return 'The target must be a same-site path or an https:// URL on this site.';
        }

        // Multi-step cycles: follow the existing chain from $to; landing
        // back on $from means this pair closes a loop.
        $cursor = $to;
        for ($hops = 0; $hops < 10; $hops++) {
            if ($cursor === $from) {
                return 'This redirect creates a loop with an existing redirect.';
            }
            $next = self::lookupExistingTarget($cursor, $ignoreId);
            if ($next === null) {
                break;
            }
            $cursor = $next;
        }

        return [$from, $to];
    }

    /** The stored target of an existing redirect for a path, ignoring one row id. */
    private static function lookupExistingTarget(string $path, ?int $ignoreId): ?string
    {
        try {
            $query = Redirect::query()->where('old_url', $path);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
            $row = $query->first();

            return $row !== null ? trim((string) $row->new_url) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * A destination is allowed when it is a same-site path (relative, or
     * an absolute URL on this site) — protocol-relative targets (//host),
     * other schemes (javascript:, data:, ...) and foreign hosts are
     * rejected so the endpoint can never be turned into an open redirect.
     */
    private static function isAllowedTo(string $to): bool
    {
        if (str_starts_with($to, '//')) {
            return false; // protocol-relative = foreign host
        }

        if (preg_match('#^https?://#i', $to)) {
            $host = strtolower((string) parse_url($to, PHP_URL_HOST));
            $host = preg_replace('/:\d+$/', '', $host);

            return $host !== '' && in_array($host, self::allowedHosts(), true);
        }

        // Relative path form: no scheme allowed at all.
        return preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $to) !== 1;
    }

    /** Hosts this site may redirect to itself under: APP_URL + ALLOWED_HOSTS. */
    private static function allowedHosts(): array
    {
        $hosts = [];
        $appUrl = strtolower((string) (parse_url((string) env('APP_URL', ''), PHP_URL_HOST) ?? ''));
        if ($appUrl !== '') {
            $hosts[] = preg_replace('/:\d+$/', '', $appUrl);
        }
        foreach (explode(',', strtolower(trim((string) env('ALLOWED_HOSTS', '')))) as $candidate) {
            $candidate = preg_replace('/:\d+$/', '', trim($candidate));
            if ($candidate !== '') {
                $hosts[] = $candidate;
            }
        }

        return array_values(array_filter(array_unique($hosts)));
    }

    /** The redirect target: control characters stripped. */
    private static function normalizeTo(string $to): string
    {
        $to = preg_replace('/[\x00-\x1f\x7f]/', '', trim($to)) ?? trim($to);
        // Relative targets get the same trailing-slash normalization as
        // from-paths; a bare "/" stays "/".
        if (!preg_match('#^https?://#i', $to)) {
            $trimmed = rtrim($to, '/');
            $to = $trimmed === '' ? '/' : $trimmed;
        }

        return $to;
    }

    public function index(): void
    {
        $redirects = Redirect::orderBy('old_url')->get();
        echo \Flight::get('blade')->render('admin.redirects.index', compact('redirects'));
    }

    public function store(): void
    {
        $data = Flight::request()->data->getData();

        $validated = self::validatePair(
            (string) ($data['old_url'] ?? ''),
            (string) ($data['new_url'] ?? ''),
            null
        );
        if (is_string($validated)) {
            Flight::redirect('/admin/redirects?error=' . urlencode($validated));
            return;
        }
        [$from, $to] = $validated;
        $type = ((int) ($data['type'] ?? 301)) === 302 ? 302 : 301;

        // From-paths are unique: updating an existing row is the expected
        // admin flow (edit the target, not a second entry).
        Redirect::updateOrInsert(
            ['old_url' => $from],
            ['new_url' => $to, 'type' => $type]
        );

        \App\Middleware\PageCache::clear();
        Flight::redirect('/admin/redirects?status=created');
    }

    public function update($id): void
    {
        $redirect = Redirect::find($id);
        if (!$redirect) {
            Flight::redirect('/admin/redirects?error=notfound');
            return;
        }

        $data = Flight::request()->data->getData();

        $validated = self::validatePair(
            (string) ($data['old_url'] ?? ''),
            (string) ($data['new_url'] ?? ''),
            (int) $id
        );
        if (is_string($validated)) {
            Flight::redirect('/admin/redirects?error=' . urlencode($validated));
            return;
        }
        [$from, $to] = $validated;

        $redirect->old_url = $from;
        $redirect->new_url = $to;
        $redirect->type = ((int) ($data['type'] ?? 301)) === 302 ? 302 : 301;
        $redirect->save();

        \App\Middleware\PageCache::clear();
        Flight::redirect('/admin/redirects?status=updated');
    }

    public function destroy($id): void
    {
        $redirect = Redirect::find($id);
        if ($redirect) {
            $redirect->delete();
            \App\Middleware\PageCache::clear();
        }

        Flight::redirect('/admin/redirects?status=deleted');
    }

    /** From-paths are stored as a single normalized path: /old-path. */
    private static function normalizeFrom(string $from): string
    {
        $from = parse_url(trim($from), PHP_URL_PATH) ?: '';
        $from = '/' . ltrim($from, '/');
        return rtrim($from, '/') === '' ? '/' : rtrim($from, '/');
    }

    /**
     * Runtime redirect lookup: applies the FIRST matching redirect for
     * the current request path. Called early (public/index.php, before
     * the framework boots) and after every page-cache lookup miss.
     *
     * @return array{0: string, 1: int}|null [target, statusCode]
     */
    public static function matchFor(string $path): ?array
    {
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        try {
            if (!\Illuminate\Database\Capsule\Manager::schema()->hasTable('redirects')) {
                return null;
            }

            $row = Redirect::query()
                ->whereIn('old_url', [$path, rtrim($path, '/') ?: '/'])
                ->first();

            if (!$row) {
                return null;
            }

            $target = trim((string) $row->new_url);
            if ($target === '') {
                return null;
            }

            return [$target, ((int) $row->type) === 302 ? 302 : 301];
        } catch (\Throwable $e) {
            error_log('Redirect lookup failed: ' . $e->getMessage());
            return null;
        }
    }
}
