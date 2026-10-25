<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
</head>
<body class="@yield('body_class')">

<a class="skip-link" href="#main-content">Skip to content</a>

<!-- ===== BEFORE HEADER ===== -->
{!! blocks_html('before_header') !!}

@include('partials.header')

<!-- ===== AFTER HEADER ===== -->
{!! blocks_html('after_header') !!}

<!-- ===== MAIN CONTENT ===== -->
@hasSection('no_container')
    <main id="main-content">
        @yield('content')
    </main>
@else
    <main id="main-content" class="section">
        <div class="container">
            @yield('content')
        </div>
    </main>
@endif

<!-- ===== BEFORE FOOTER (CTA / Newsletter) ===== -->
{!! blocks_html('before_footer') !!}

<!-- ===== MAIN FOOTER ===== -->
@include('partials.footer')

<!-- ===== AFTER FOOTER (Scripts / Cookie bar) ===== -->
{!! blocks_html('after_footer') !!}

@include('partials.scripts')


{{-- View beacon: cached pages never reach PHP rendering, so the
     layout ships a tiny sendBeacon ping to POST /_v (handled
     pre-bootstrap in public/index.php). Browsers only. --}}
<script>
(function () {
    if (/bot|crawl|spider|slurp|curl|wget|headless/i.test(navigator.userAgent)) { return; }
    try {
        navigator.sendBeacon('/_v', new URLSearchParams({ p: location.pathname }));
    } catch (e) { /* analytics must never break the page */ }
})();
</script>
</body>
</html>
