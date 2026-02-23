<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
</head>
<body>
    @includeIf(theme_view('blocks.topbar'))
    @includeIf(theme_view('blocks.header'))

    <main>
        @yield('content')
    </main>

    @includeIf(theme_view('blocks.footer'))

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ theme_asset('js/theme.js') }}"></script>
</body>
</html>
