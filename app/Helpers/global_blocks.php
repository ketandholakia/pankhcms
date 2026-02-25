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
            if (!is_array($content)) {
                // Many installs store raw HTML/Blade in `content` as a string.
                // Compile any Blade tags in the content so helpers like `setting()`
                // and `menu()` are executed. Fall back to raw content on error.
                $compiledContent = $content;
                try {
                    $blade = \Flight::get('blade');
                    $compiler = $blade->getContainer()['blade.compiler'];
                    $php = $compiler->compileString($content);
                    $__env = $blade->getContainer()['view'];
                    ob_start();
                    try {
                        eval('?>' . $php);
                        $compiledContent = ob_get_clean();
                    } catch (Throwable $e) {
                        ob_end_clean();
                        throw $e;
                    }
                } catch (Throwable $e) {
                    // keep original content
                }

                $renderData = [
                    'text' => $compiledContent,
                    'title' => $block->title ?? '',
                    'show_title' => $block->show_title ?? true,
                    'block' => $block
                ];
                $html .= render_block($type, ['data' => $renderData]);
            } else {
                // Ensure show_title flag is present for array-based content too
                if (!isset($content['data'])) {
                    $content['data']['show_title'] = $block->show_title ?? true;
                } else {
                    $content['data']['show_title'] = $content['data']['show_title'] ?? ($block->show_title ?? true);
                }
                $html .= render_block($type, $content);
            }
        }
        return $html;
    }
}
