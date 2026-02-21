<!DOCTYPE html>
<html lang="en">

<head>


    <meta name="viewport" content="width=device-width, initial-scale=1">




  <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="/themes/greenbs/assets/css/owl.carousel.min.css" rel="stylesheet">
    

    <!-- Customized Bootstrap Stylesheet -->
    <link href="/themes/greenbs/assets/css//bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="/themes/greenbs/assets/css/styles.css" rel="stylesheet">


</head>


<body>


  @include('blocks.topbar')
  @include('blocks.header')

  <main>
    @yield('content')
  </main>


  @include('blocks.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/themes/greenbs/assets/js/main.js"></script>

</body>
</html>