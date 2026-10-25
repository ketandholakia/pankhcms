<?php

/**
 * Site search: parameterized LIKE matching over the publicly visible
 * pages (status = published AND (published_at IS NULL OR <= now), the
 * same semantics as Page::visible()).
 *
 * The page body lives in content_json blocks (html/text keys), with the
 * legacy `content` column still searched for imported pages, so search
 * finds block-based and legacy content alike.
 *
 * FTS5 was dropped deliberately: pages_fts was an external-content table
 * that nothing ever populated, FTS5 does not exist on MySQL/MariaDB (the
 * schema must work on both), and the LIKE fallback needed escaping
 * anyway. LIKE over the text sources is portable and correct.
 */
class SearchPlugin
{
    /** Maximum results returned. */
    private const MAX_RESULTS = 50;

    /** Maximum accepted query length. */
    private const MAX_QUERY_LENGTH = 100;

    public function boot()
    {
        Flight::route('GET /search', function () {
            $q = self::normalizeQuery((string) (Flight::request()->query->q ?? ''));
            $pages = $q === '' ? collect() : self::searchPages($q);

            $blade = Flight::get('blade');
            if ($blade->exists('site.search')) {
                echo $blade->render('site.search', compact('pages', 'q'));
                return;
            }

            // No search view anywhere: minimal escaped fallback.
            echo '<h1>Search Results for: ' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') . '</h1>';
            if ($pages->isEmpty()) {
                echo '<p>No results found.</p>';
                return;
            }
            echo '<ul>';
            foreach ($pages as $p) {
                $url = '/' . $p->slug;
                echo '<li><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($p->title, ENT_QUOTES, 'UTF-8') . '</a></li>';
            }
            echo '</ul>';
        });
    }

    /**
     * Trim and cap the raw query string; returns '' for empty input.
     */
    public static function normalizeQuery(string $q): string
    {
        $q = trim($q);
        if ($q === '') {
            return '';
        }
        return mb_substr($q, 0, self::MAX_QUERY_LENGTH);
    }

    /**
     * Search publicly visible pages by title, slug, legacy content or
     * the text inside content_json blocks. LIKE wildcards in the query
     * are escaped ('!' as the escape character — portable across SQLite
     * and MySQL, unlike backslash) and the pattern is always bound as a
     * parameter. Returns exactly the fields the search view uses.
     */
    public static function searchPages(string $q): \Illuminate\Support\Collection
    {
        $needle = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';

        $rows = \App\Models\Page::visible()
            ->where(function ($outer) use ($needle) {
                // ESCAPE '!' must be explicit: SQLite's LIKE has no default
                // escape character and MySQL's default (backslash) is not
                // portable. Column names are fixed identifiers; the needle
                // is always a bound parameter.
                $outer->whereRaw("title LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("content LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("content_json LIKE ? ESCAPE '!'", [$needle]);
            })
            ->orderBy('title')
            ->limit(self::MAX_RESULTS)
            ->get(['id', 'title', 'slug', 'content', 'content_json']);

        // The view shows a plain-text excerpt: block text first, legacy
        // content as fallback.
        return $rows->map(function ($page) {
            $body = self::blockText($page->content_json);
            if ($body === '') {
                $body = (string) $page->content;
            }
            return (object) [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => mb_substr(trim(strip_tags($body)), 0, 200),
            ];
        });
    }

    /**
     * Concatenate the html/text values of a content_json block list.
     */
    private static function blockText(?string $contentJson): string
    {
        if ($contentJson === null || $contentJson === '') {
            return '';
        }
        $blocks = json_decode($contentJson, true);
        if (!is_array($blocks)) {
            return '';
        }
        $text = '';
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $text .= ' ' . (string) ($block['html'] ?? '') . ' ' . (string) ($block['text'] ?? '');
        }
        return trim($text);
    }
}
