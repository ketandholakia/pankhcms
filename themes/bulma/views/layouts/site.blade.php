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
        <nav class="breadcrumb container" aria-label="breadcrumbs">
            <ul>
                @foreach($breadcrumbs as $crumb)
                    <li @class(['is-active' => $loop->last])>
                        @if(!$loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                        @else
                            <a href="#" aria-current="page">{{ $crumb['title'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
        @if(setting('breadcrumbs_schema') === '1')
            {!! breadcrumbSchema($breadcrumbs) !!}
        @endif
    @endif

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
    <style>
        /* Align custom menu inside Bulma navbar */
        .navbar-start,
        .navbar-end {
            align-items: center;
        }

        /* ====== MENU BASE ====== */

        .theme-menu-list {
            display: flex;
            align-items: center;
            height: 100%;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .theme-menu-item {
            position: relative;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .theme-menu-link {
            display: flex;
            align-items: center;
            height: 3.25rem; /* Bulma navbar height */
            padding: 0 0.75rem;
            font-weight: 500;
            color: #363636;
            border-radius: 6px;
        }

        .theme-menu-link:hover {
            background: #f5f5f5;
            color: #000;
        }

        /* ====== SUBMENU ====== */

        .theme-submenu {
            list-style: none;
            padding: 0.5rem 0;
            margin: 0;

            position: absolute;
            top: 100%;
            left: 0;

            min-width: 220px;

            background: #fff;
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.15);

            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: all 0.2s ease;

            z-index: 999;
        }

        .theme-submenu li a {
            display: block;
            padding: 0.6rem 1rem;
            color: #363636;
            white-space: nowrap;
        }

        .theme-submenu li a:hover {
            background: #f5f5f5;
            color: #000;
        }

        /* ====== DESKTOP HOVER ====== */

        @media (min-width: 768px) {

            .theme-menu-item:hover > .theme-submenu {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .theme-menu-toggle {
                display: none;
            }
        }

        /* ====== MOBILE CLICK ====== */

        @media (max-width: 767px) {

            .theme-menu-list {
                flex-direction: column;
            }

            .theme-submenu {
                position: static;
                box-shadow: none;
                background: #fafafa;
                border-radius: 6px;
                margin-top: 0.25rem;
                opacity: 1;
                visibility: visible;
                transform: none;
                display: none;
            }

            .theme-menu-item.is-open > .theme-submenu {
                display: block;
            }
        }

        /* Toggle button */
        .theme-menu-toggle {
            margin-left: 6px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* ====== CARET ICON ====== */

        .theme-menu-caret {
            margin-left: 6px;
            font-size: 0.75rem;
            opacity: 0.7;
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        /* Rotate on hover (desktop) */

        @media (min-width: 768px) {
            .theme-menu-item:hover > .theme-menu-head .theme-menu-caret {
                transform: rotate(180deg);
            }
        }

        /* Rotate when open (mobile) */

        .theme-menu-item.is-open > .theme-menu-head .theme-menu-caret {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    

    <main>
        @yield('content')
    </main>

   
    <script>
        document.addEventListener('click', function (event) {
            const toggle = event.target.closest('.theme-menu-toggle');
            const openItems = document.querySelectorAll('.theme-menu-item.is-open');

            if (!toggle) {
                openItems.forEach(function (item) {
                    item.classList.remove('is-open');
                    const btn = item.querySelector('.theme-menu-toggle');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
                return;
            }

            const item = toggle.closest('.theme-menu-item');
            const willOpen = !item.classList.contains('is-open');

            openItems.forEach(function (openItem) {
                openItem.classList.remove('is-open');
                const btn = openItem.querySelector('.theme-menu-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });

            if (willOpen) {
                item.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    </script>
</body>
</html>
