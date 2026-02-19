<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
{{ ($page->meta_title ?? null)
   ?: ($page->title ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}
</title>

<meta name="description"
      content="{{ ($page->meta_description ?? null)
      ?: ($seo_default_description ?? $site_description ?? '') }}">

<meta name="keywords"
      content="{{ ($page->meta_keywords ?? null)
      ?: ($seo_default_keywords ?? '') }}">


{{-- Open Graph --}}
<meta property="og:title"
      content="{{ ($page->og_title ?? null)
      ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">

<meta property="og:description"
      content="{{ ($page->og_description ?? null)
      ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">

<meta property="og:image"
      content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">

<meta property="og:url"
      content="{{ ($page->canonical_url ?? null)
      ?: ($canonical_base ?? env('APP_URL') . \Flight::request()->url) }}">

<meta property="og:type" content="website">


{{-- Canonical --}}
<link rel="canonical"
      href="{{ ($page->canonical_url ?? null)
      ?: ($canonical_base ?? env('APP_URL') . \Flight::request()->url) }}">


{{-- Robots --}}
<meta name="robots"
      content="{{ ($page->robots ?? null)
      ?: ($robots_default ?? 'index, follow') }}">


{{-- Twitter --}}
<meta name="twitter:card"
      content="{{ ($page->twitter_card ?? null)
      ?: ($twitter_card ?? 'summary_large_image') }}">

<meta name="twitter:site"
      content="{{ ($page->twitter_site ?? null)
      ?: ($twitter_site ?? '') }}">



{{-- ===== Assets ===== --}}

<link rel="icon" href="{{ theme_asset('img/favicon.ico') }}">

<link rel="preconnect" href="https://fonts.gstatic.com">

<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap"
      rel="stylesheet">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css">

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">

<link rel="stylesheet"
      href="{{ theme_asset('lib/owlcarousel/assets/owl.carousel.min.css') }}">

<link rel="stylesheet"
      href="{{ theme_asset('css/bootstrap.min.css') }}">

<link rel="stylesheet"
      href="{{ theme_asset('css/style.css') }}">

</head>

<body>

@include('partials.topbar')
@include('partials.navbar')

<main>
    @yield('content')
</main>

@include('partials.footer')
@include('partials.scripts')

</body>
</html>
