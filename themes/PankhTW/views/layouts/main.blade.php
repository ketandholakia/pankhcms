<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')

    <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
</head>
<body class="bg-slate-50 text-slate-900">
    @includeIf(theme_view('blocks.topbar'))
    @includeIf(theme_view('blocks.header'))

    <main>
        @yield('content')
    </main>

    @includeIf(theme_view('blocks.footer'))
</body>
</html>
