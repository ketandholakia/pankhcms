<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($page->meta_title ?? null) ?: ($page->title ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}</title>
    <meta name="description" content="{{ ($page->meta_description ?? null) ?: ($seo_default_description ?? $site_description ?? '') }}">
    <meta name="keywords" content="{{ ($page->meta_keywords ?? null) ?: ($seo_default_keywords ?? '') }}">

    {{-- Open Graph / Social Sharing --}}
    <meta property="og:title" content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">
    <meta property="og:description" content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">
    <meta property="og:image" content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">
    <meta property="og:url" content="{{ ($page->canonical_url ?? null) ?: ($canonical_base ?? env('APP_URL') . \Flight::request()->url) }}">
    <meta property="og:type" content="website">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ ($page->canonical_url ?? null) ?: ($canonical_base ?? env('APP_URL') . \Flight::request()->url) }}">

    {{-- Robots --}}
    <meta name="robots" content="{{ ($page->robots ?? null) ?: ($robots_default ?? 'index, follow') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ ($page->twitter_card ?? null) ?: ($twitter_card ?? 'summary_large_image') }}">
    <meta name="twitter:site" content="{{ ($page->twitter_site ?? null) ?: ($twitter_site ?? '') }}">
    <meta name="twitter:title" content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">
    <meta name="twitter:description" content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">
    <meta name="twitter:image" content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">

    {{-- Breadcrumbs --}}
    @if(setting('breadcrumbs_enabled') === '1' && !empty($breadcrumbs))
        <nav aria-label="breadcrumb" class="py-3">
            <ol class="breadcrumb container mb-0">
                @foreach($breadcrumbs as $crumb)
                    <li class="breadcrumb-item @class(['active' => $loop->last])">
                        @if(!$loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                        @else
                            {{ $crumb['title'] }}
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
        @if(setting('breadcrumbs_schema') === '1')
            {!! breadcrumbSchema($breadcrumbs) !!}
        @endif
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
    <style>
        /* Desktop Hover for Bootstrap Dropdowns */
        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">PankhCMS Bootstrap</a>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="navbar-nav">
                    {!! render_menu('header') !!}
                </div>
                <form action="/search" method="GET" class="d-flex gap-2">
                    <input type="text" name="q" placeholder="Search..." class="form-control" required>
                    <button class="btn btn-dark" type="submit">Search</button>
                </form>
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
