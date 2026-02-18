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
  <meta name="twitter:card" content="{{ ($page->twitter_card ?? null) ?: ($twitter_card ?? 'summary_large_image') }}">
  <meta name="twitter:site" content="{{ ($page->twitter_site ?? null) ?: ($twitter_site ?? '') }}">
  <meta name="twitter:title" content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">
  <meta name="twitter:description" content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">
  <meta name="twitter:image" content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">


  <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/flywind-core.css') }}">
  <link rel="stylesheet" href="{{ \App\Core\Theme::asset('css/custom.css') }}">
</head>

<body>

<header class="site-header">
  <div class="container nav">

    <strong>{{ $site_title ?? 'PankhCMS' }}</strong>

    @include('blocks.menu')

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

</body>
</html>
