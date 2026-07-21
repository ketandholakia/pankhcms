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

</body>
</html>
