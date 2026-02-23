@include(theme_view('blocks.hero'), ['block' => [
    'title' => $title ?? ($block['title'] ?? ''),
    'subtitle' => $subtitle ?? ($block['subtitle'] ?? ''),
]])
