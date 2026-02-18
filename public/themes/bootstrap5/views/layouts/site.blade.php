<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($page->meta_title ?? null) ?: ($page->title ?? 'PankhCMS') }}</title>
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">PankhCMS Bootstrap</a>
            <div class="navbar-nav ms-auto">
                {!! render_menu('header') !!}
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="py-4 border-top bg-white mt-5">
        <div class="container text-center text-muted small">
            Powered by PankhCMS Theme System
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
