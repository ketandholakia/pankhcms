<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Dynamic Title -->
    <title>
        {{ $page->meta_title ?? $page->title ?? setting('site_title') ?? 'PankhCMS' }}
    </title>

    <!-- Optional SEO -->
    <meta name="description" content="{{ $page->meta_description ?? setting('default_meta_description') }}">
    
    <!-- Favicon -->
    @if(setting('favicon'))
        <link rel="icon" href="{{ setting('favicon') }}">
    @endif

    <!-- Bulma -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">

    <!-- Bulma Carousel CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma-carousel@4.0.4/dist/css/bulma-carousel.min.css">

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/themes/pankhcmsstarter/assets/css/style.css">

</head>
<body>

<!-- ===== BEFORE HEADER ===== -->
{!! blocks_html('before_header') !!}

@include('partials.header')

<!-- ===== AFTER HEADER ===== -->
{!! blocks_html('after_header') !!}

<!-- ===== MAIN CONTENT ===== -->
<main class="section">
    <div class="container">
        @yield('content')
    </div>
</main>

<!-- ===== BEFORE FOOTER (CTA / Newsletter) ===== -->
{!! blocks_html('before_footer') !!}

<!-- ===== MAIN FOOTER ===== -->
{!! blocks_html('footer') !!}

<!-- ===== AFTER FOOTER (Scripts / Cookie bar) ===== -->
{!! blocks_html('after_footer') !!}

<!-- Theme JS -->
<script src="https://cdn.jsdelivr.net/npm/bulma-carousel@4.0.4/dist/js/bulma-carousel.min.js"></script>
<script src="/themes/pankhcmsstarter/assets/js/app.js"></script>

<!-- Lucide icons (SVG) -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons();</script>

</body>
</html>