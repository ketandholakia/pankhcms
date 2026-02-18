<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
  <title>{{ ($page->meta_title ?? null) ?: ($title ?? ($site_title ?? ($seo_default_title ?? 'PankhCMS'))) }}</title>
  <meta name="description" content="{{ ($page->meta_description ?? null) ?: ($seo_default_description ?? '') }}">
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
  <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/flywind-core.css') }}">
  <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
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
                          <span>{{ $crumb['title'] }}</span>
                      @endif
                  </li>
                  @if(!$loop->last)
                      <span class="breadcrumb-separator">{{ setting('breadcrumbs_separator', '/') }}</span>
                  @endif
              @endforeach
          </ul>
      </nav>
      @if(setting('breadcrumbs_schema') === '1')
          {!! breadcrumbSchema($breadcrumbs) !!}
      @endif
  @endif


  <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/flywind-core.css') }}">
  <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
  <style>
        .breadcrumb { margin-top: 1rem; margin-bottom: 1rem; }
        .breadcrumb ul { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; align-items: center; }
        .breadcrumb li { display: flex; align-items: center; }
        .breadcrumb li a { text-decoration: none; color: var(--primary); }
        .breadcrumb li.is-active span { color: var(--muted); }
        .breadcrumb-separator { margin: 0 0.5rem; color: var(--muted); }
  </style>
</head>

<body>

<header class="site-header">
  <div class="container nav">

    <strong>{{ $site_title ?? 'PankhCMS' }}</strong>

    {!! render_menu('navbar') !!}


  </div>
</header>

{{-- Optional hero block --}}
@if(isset($hero))
  @include('blocks.hero')
@endif

<main class="container layout mt-2">

  <section>
    @yield('content')
  </section>

  @include('blocks.sidebar')

</main>

<footer class="site-footer">
  <div class="container">
    © {{ date('Y') }} {{ $site_title ?? 'PankhCMS' }}
  </div>
</footer>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav');

    if (navToggle && mainNav) {
      navToggle.addEventListener('click', function() {
        mainNav.classList.toggle('open');
        const isOpen = mainNav.classList.contains('open');
        navToggle.setAttribute('aria-expanded', isOpen);
      });
    }

    // Close mobile menu when a link is clicked (optional, but good for UX)
    mainNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => mainNav.classList.remove('open'));
    });
  });
</script>
</body>
</html>
