<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{{ $page->title ?? setting('site_name') }}</title>

<link rel="icon" href="{{ theme_asset('favicon.ico') }}">

<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>

<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet">

<link href="{{ theme_asset('css/styles.css') }}" rel="stylesheet">
</head>

<body id="page-top">

@include('partials.navbar')

<main>
    @yield('content')
</main>

@include('partials.footer')
@include('partials.scripts')

</body>
</html>
