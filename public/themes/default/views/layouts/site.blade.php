<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  {{-- ================= SEO ================= --}}

  <title>{{ ($page->meta_title ?? null) ?: ($title ?? ($site_title ?? ($seo_default_title ?? 'PankhCMS'))) }}</title>

  <meta name="description"
        content="{{ ($page->meta_description ?? null) ?: ($seo_default_description ?? '') }}">

  <meta name="keywords"
        content="{{ ($page->meta_keywords ?? null) ?: ($seo_default_keywords ?? '') }}">

  {{-- Open Graph --}}
  <meta property="og:title"
        content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">

  <meta property="og:description"
        content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">

  <meta property="og:image"
        content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">

  <meta property="og:url"
        content="{{ ($page->canonical_url ?? null) ?: ($canonical_base ?? env('APP_URL') . \Flight::request()->url) }}">

  <meta property="og:type" content="website">

  {{-- Canonical --}}
  <link rel="canonical"
        href="{{ ($page->canonical_url ?? null) ?: ($canonical_base ?? env('APP_URL') . \Flight::request()->url) }}">

  {{-- Robots --}}
  <meta name="robots"
        content="{{ ($page->robots ?? null) ?: ($robots_default ?? 'index, follow') }}">

  {{-- Twitter --}}
  <meta name="twitter:card"
        content="{{ ($page->twitter_card ?? null) ?: ($twitter_card ?? 'summary_large_image') }}">

  <meta name="twitter:site"
        content="{{ ($page->twitter_site ?? null) ?: ($twitter_site ?? '') }}">

  <meta name="twitter:title"
        content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">

  <meta name="twitter:description"
        content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">

  <meta name="twitter:image"
        content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">

  {{-- ================= CSS ================= --}}

  <link rel="stylesheet"
        href="{{ \App\Core\Theme::asset('css/flywind-core.css') }}">

  <link rel="stylesheet"
        href="{{ \App\Core\Theme::asset('css/custom.css') }}">

</head>


<body>

{{-- =====================================================
   HEADER / NAVBAR
===================================================== --}}

<header class="site-header">

  <div class="container nav">

    <a href="/" class="site-logo">
      {{ $site_title ?? 'PankhCMS' }}
    </a>

    <button id="nav-toggle"
            class="nav-toggle"
            aria-label="Toggle navigation"
            aria-expanded="false">
      ☰
    </button>

    <nav class="main-nav">
      @php
        $menuTree = menu_tree('navbar');
        if (empty($menuTree)) {
            $menuTree = menu_tree('header');
        }
      @endphp
      @include('blocks.menu', ['tree' => $menuTree])
    </nav>

  </div>

</header>


{{-- =====================================================
   BREADCRUMBS
===================================================== --}}

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
          <span class="breadcrumb-separator">
            {{ setting('breadcrumbs_separator', '/') }}
          </span>
        @endif

      @endforeach

    </ul>

  </nav>

  @if(setting('breadcrumbs_schema') === '1')
    {!! breadcrumbSchema($breadcrumbs) !!}
  @endif

@endif


{{-- =====================================================
   HERO BLOCK (optional)
===================================================== --}}

@if(isset($hero))
  @include('blocks.hero')
@endif


{{-- =====================================================
   MAIN CONTENT AREA
===================================================== --}}

<main class="container layout mt-2">

  <section>
    @yield('content')
  </section>

  @include('blocks.sidebar')

</main>


{{-- =====================================================
   FOOTER
===================================================== --}}

<footer class="site-footer">

  <div class="container">
    © {{ date('Y') }} {{ $site_title ?? 'PankhCMS' }}
  </div>

</footer>


{{-- =====================================================
   MOBILE NAV SCRIPT
===================================================== --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

  const toggle = document.getElementById('nav-toggle');
  const nav = document.querySelector('.main-nav');

  if (!toggle || !nav) return;

  toggle.addEventListener('click', function () {

    nav.classList.toggle('open');

    const isOpen = nav.classList.contains('open');
    toggle.setAttribute('aria-expanded', isOpen);

  });

});

</script>

</body>
</html>
