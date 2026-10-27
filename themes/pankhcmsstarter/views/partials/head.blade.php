@php
    $currentPage = $page ?? null;
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

@include('partials.seo', ['currentPage' => $currentPage])

<meta name="color-scheme" content="light dark">
<link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
