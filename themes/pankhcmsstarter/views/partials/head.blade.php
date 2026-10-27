@php
    $currentPage = $page ?? null;
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

@include('partials.seo', ['currentPage' => $currentPage])

<meta name="color-scheme" content="light dark">
@php
    // Theme options -> CSS variables (values validated as hex/number on
    // save in ThemeController::saveOptions).
    $themeOptionVars = [];
    foreach (\App\Core\Theme::options() as $optionKey => $themeOption) {
        if ($themeOption['css'] !== '' && trim((string) $themeOption['value']) !== '') {
            $themeOptionVars[] = $themeOption['css'] . ': ' . $themeOption['value'] . $themeOption['unit'] . ';';
        }
    }
@endphp
@if(count($themeOptionVars))
<style>:root { {{ implode(' ', $themeOptionVars) }} }</style>
@endif
<link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
