<?php
use App\Models\GlobalBlock;
use App\Models\BlockPlacement;

if (!function_exists('blocks')) {
    /**
     * Fetch all active global blocks for a given location, ordered by sort_order.
     * @param string $location
     * @return \Illuminate\Support\Collection
     */
    function blocks($location) {
        return GlobalBlock::where('status', 1)
            // Query the real DB column `section` (accessors map `location` → `section`)
            ->whereHas('placements', function($q) use ($location) {
                $q->where('section', $location);
            })
            ->with(['placements' => function($q) use ($location) {
                // The DB column is named `order`; model provides a `sort_order`
                // accessor, so query the real column to avoid SQL errors.
                $q->where('section', $location)->orderBy('order');
            }])
            ->get()
            ->sortBy(function($block) use ($location) {
                return optional($block->placements->first())->sort_order ?? 0;
            });
    }
}

if (!function_exists('render_string_block')) {
    /**
     * Render a string block without Blade/eval. Supports a small whitelist
     * of placeholders — {{ setting('key') }} — and returns sanitized HTML.
     * Raw PHP, @php blocks, @include and other Blade directives are NOT
     * executed; they are stripped by the sanitizer.
     */
    function render_string_block(string $content): string
    {
        // Strip Blade directives and raw PHP — block content is never compiled.
        $content = preg_replace('/@php\b.*?@endphp/s', '', $content) ?? $content;
        $content = preg_replace('/@php\b/', '', $content) ?? $content;
        $content = preg_replace('/@include\([^)]*\)/', '', $content) ?? $content;

        $html = preg_replace_callback(
            '/\{\{\s*setting\(\s*([\'"])([a-zA-Z0-9_.\-]+)\1\s*\)\s*\}\}/',
            static function ($m) {
                return htmlspecialchars((string) setting($m[2], ''), ENT_QUOTES, 'UTF-8');
            },
            $content
        ) ?? $content;

        return sanitize_html($html);
    }
}

if (!function_exists('blocks_html')) {
    /**
     * Render active global blocks for a location into HTML.
     * Keeps blocks() returning a collection for themes that iterate.
     */
    function blocks_html(string $location): string
    {
        $html = '';
        foreach (blocks($location) as $block) {
            $type = (string) ($block->type ?? '');
            if ($type === '') {
                continue;
            }

            $content = $block->content ?? [];

            // The admin controller stores JSON strings through the model's
            // 'array' cast, which double-encodes them — unwrap every JSON
            // layer so array-shaped content actually reaches the array
            // branch. Plain HTML strings stay strings.
            for ($unwrap = 0; $unwrap < 2 && is_string($content); $unwrap++) {
                $trimmed = ltrim($content);
                if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
                    break;
                }
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $content = $decoded;
                }
            }

            if (!is_array($content)) {
                // Many installs store raw HTML in `content` as a string.
                // It is rendered through the placeholder whitelist and the
                // allowlist sanitizer — never compiled as Blade.
                $renderData = [
                    'text' => render_string_block((string) $content),
                    'title' => $block->title ?? '',
                    'show_title' => $block->show_title ?? true,
                    'block' => $block
                ];
                try {
                    $html .= render_block($type, ['data' => $renderData]);
                } catch (\Throwable $e) {
                    error_log('Block render failed [' . $type . ']: ' . $e->getMessage());
                }
            } else {
                // Ensure show_title flag is present for array-based content too
                if (!isset($content['data'])) {
                    $content['data']['show_title'] = $block->show_title ?? true;
                } else {
                    $content['data']['show_title'] = $content['data']['show_title'] ?? ($block->show_title ?? true);
                }
                // Rich-text fields stored as arrays are sanitized at render
                // time as well (blocks may predate the save-time sanitizer).
                foreach (['text', 'html'] as $field) {
                    if (isset($content['data'][$field]) && is_string($content['data'][$field])) {
                        $content['data'][$field] = sanitize_html($content['data'][$field]);
                    }
                }
                try {
                    $html .= render_block($type, $content);
                } catch (\Throwable $e) {
                    error_log('Block render failed [' . $type . ']: ' . $e->getMessage());
                }
            }
        }
        return $html;
    }
}
