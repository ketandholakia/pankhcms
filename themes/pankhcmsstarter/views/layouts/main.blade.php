<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title ?? 'PankhCMS' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>

{!! blocks_html('before_header') !!}

@include('partials.header')

{!! blocks_html('after_header') !!}

<main>
    @yield('content')
</main>

{!! blocks_html('before_footer') !!}


    {!! blocks_html('footer') !!}
    <div style="background:#ffe0e0; color:#900; padding:10px; margin:10px 0;">
        <strong>Debug footer block output:</strong>
        <pre>{{ var_export(blocks_html('footer'), true) }}</pre>
    </div>

<!-- @include('partials.footer') -->

{!! blocks_html('after_footer') !!}

<script src="/themes/pankhcmsstarter/assets/js/app.js"></script>

</body>
</html>
