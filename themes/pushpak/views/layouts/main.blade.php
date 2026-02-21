<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta_title ?? $site_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/themes/pushpak/assets/css/pushpak.css">
</head>
<body>

<body>

  @include('blocks.header')

  <main>
    @yield('content')
  </main>

  @include('blocks.footer')

  
   @include('blocks.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/themes/pushpak/assets/js/pushpak.js"></script>

</body>
</html>