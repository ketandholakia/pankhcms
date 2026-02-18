<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($page->meta_title ?? null) ?: ($page->title ?? 'PankhCMS') }}</title>
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
</head>
<body>
    <nav class="navbar is-light" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item has-text-weight-bold" href="/">PankhCMS Bulma</a>
            </div>
            <div class="navbar-menu is-active">
                <div class="navbar-start">
                    {!! render_menu('header') !!}
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="footer py-5">
        <div class="content has-text-centered">
            <p>Powered by PankhCMS Theme System</p>
        </div>
    </footer>
</body>
</html>
