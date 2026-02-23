<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $page->title ?? ($site_name ?? 'Site Name') }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ theme_asset('css/normalize.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/milligram.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/styles.css') }}">
</head>

<body>

@include('blocks.header')

<main class="container">
    @yield('content')
</main>

@include('blocks.footer')

<script src="{{ theme_asset('js/main.js') }}"></script>

</body>
</html>