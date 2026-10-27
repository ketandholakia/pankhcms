<?php

namespace App\Controllers\Admin;

use App\Models\Redirect;
use Flight;

class RedirectController
{
    public function index(): void
    {
        $redirects = Redirect::orderBy('old_url')->get();
        echo \Flight::get('blade')->render('admin.redirects.index', compact('redirects'));
    }

    public function store(): void
    {
        $data = Flight::request()->data->getData();

        $from = $this->normalizeFrom((string) ($data['old_url'] ?? ''));
        $to = trim((string) ($data['new_url'] ?? ''));
        $type = ((int) ($data['type'] ?? 301)) === 302 ? 302 : 301;

        if ($from === '' || $to === '') {
            Flight::redirect('/admin/redirects?error=missing');
            return;
        }

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
        $from = $this->normalizeFrom((string) ($data['old_url'] ?? ''));
        $to = trim((string) ($data['new_url'] ?? ''));

        if ($from === '' || $to === '') {
            Flight::redirect('/admin/redirects?error=missing');
            return;
        }

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
    private function normalizeFrom(string $from): string
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
