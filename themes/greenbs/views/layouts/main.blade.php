<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

  @php
    $currentUrlPath = $_SERVER['REQUEST_URI'] ?? '/';
    $baseForCanonical = $canonical_base ?? env('APP_URL', '');
    if ($baseForCanonical !== '' && str_ends_with($baseForCanonical, '/')) {
      $baseForCanonical = rtrim($baseForCanonical, '/');
    }
    $canonicalFallback = ($baseForCanonical !== '' ? $baseForCanonical : '') . $currentUrlPath;
    $canonicalUrl = ($page->canonical_url ?? null) ?: $canonicalFallback;
  @endphp

  <title>{{ ($page->meta_title ?? null) ?: ($page->title ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}</title>
  <meta name="description" content="{{ ($page->meta_description ?? null) ?: ($seo_default_description ?? $site_description ?? '') }}">
  <meta name="keywords" content="{{ ($page->meta_keywords ?? null) ?: ($seo_default_keywords ?? '') }}">

  <meta property="og:title" content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">
  <meta property="og:description" content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">
  <meta property="og:image" content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">
  <meta property="og:url" content="{{ $canonicalUrl }}">
  <meta property="og:type" content="website">

  <link rel="canonical" href="{{ $canonicalUrl }}">

  <meta name="robots" content="{{ ($page->robots ?? null) ?: ($robots_default ?? 'index, follow') }}">

  <meta name="twitter:card" content="{{ ($page->twitter_card ?? null) ?: ($twitter_card ?? 'summary_large_image') }}">
  <meta name="twitter:site" content="{{ ($page->twitter_site ?? null) ?: ($twitter_site ?? '') }}">
  <meta name="twitter:title" content="{{ ($page->og_title ?? null) ?: ($og_title_default ?? ($seo_default_title ?? $site_title ?? 'PankhCMS')) }}">
  <meta name="twitter:description" content="{{ ($page->og_description ?? null) ?: ($og_description_default ?? ($seo_default_description ?? $site_description ?? '')) }}">
  <meta name="twitter:image" content="{{ ($page->og_image ?? null) ?: ($og_image_default ?? '') }}">




  <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    

    <!-- Customized Bootstrap Stylesheet -->
    <link href="/themes/greenbs/assets/css//bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="/themes/greenbs/assets/css/styles.css" rel="stylesheet">


</head>


<body>


  @include('blocks.topbar')
  @include('blocks.header')
  @php
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  @endphp
  @if($currentPath === '/')
    @include('blocks.slider_bootstrap')

    <!-- ================= HERO ================= -->
    <section class="section-padding bg-light">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <img src="https://via.placeholder.com/600x400" class="img-fluid rounded shadow" alt="">
          </div>
          <div class="col-lg-6">
            <h2 class="fw-bold">Welcome to Organic Dehydrated Foods Pvt. Ltd.</h2>
            <p class="mt-3">
              An ISO 22000:2018, WHO-GMP, FSSAI & ORGANIC Certified Company established in 2016,
              manufacturing premium Food Supplements, Herbal Oil Drops, Organic Vegetables
              and Herbal Powders using a unique dehydration technique.
            </p>
            <p>
              Our products support health and wellness while maintaining a strong connection
              between Mother Nature, farmers, and consumers.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= FEATURES ================= -->
    <section class="section-padding text-center">
      <div class="container">
        <h2 class="fw-bold mb-5">Why Choose Us</h2>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="icon-box mb-3">🌿</div>
            <h5>100% Organic</h5>
            <p>Made from carefully selected organic ingredients for maximum purity.</p>
          </div>
          <div class="col-md-4">
            <div class="icon-box mb-3">⚗️</div>
            <h5>Advanced Dehydration</h5>
            <p>Retains nutrients, potency, and natural effectiveness.</p>
          </div>
          <div class="col-md-4">
            <div class="icon-box mb-3">🤝</div>
            <h5>Farmer Empowerment</h5>
            <p>Supporting sustainable livelihoods and ethical sourcing.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= PRODUCTS ================= -->
    <section class="section-padding bg-light">
      <div class="container text-center">
        <h2 class="fw-bold mb-5">Our Product Categories</h2>
        <div class="row g-4">
          <div class="col-md-3">
            <div class="card h-100 shadow-sm">
              <img src="https://via.placeholder.com/400x300" class="card-img-top">
              <div class="card-body">
                <h5 class="card-title">Food Supplements</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card h-100 shadow-sm">
              <img src="https://via.placeholder.com/400x300" class="card-img-top">
              <div class="card-body">
                <h5 class="card-title">Herbal Oil Drops</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card h-100 shadow-sm">
              <img src="https://via.placeholder.com/400x300" class="card-img-top">
              <div class="card-body">
                <h5 class="card-title">Organic Vegetables</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card h-100 shadow-sm">
              <img src="https://via.placeholder.com/400x300" class="card-img-top">
              <div class="card-body">
                <h5 class="card-title">Herbal Powders</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= CERTIFICATIONS ================= -->
    <section class="section-padding text-center">
      <div class="container">
        <h2 class="fw-bold mb-5">Certifications</h2>
        <div class="row g-4">
          <div class="col-md-3">
            <img src="https://via.placeholder.com/200x120" class="img-fluid">
            <p class="mt-2">ISO 22000:2018</p>
          </div>
          <div class="col-md-3">
            <img src="https://via.placeholder.com/200x120" class="img-fluid">
            <p class="mt-2">WHO-GMP</p>
          </div>
          <div class="col-md-3">
            <img src="https://via.placeholder.com/200x120" class="img-fluid">
            <p class="mt-2">FSSAI</p>
          </div>
          <div class="col-md-3">
            <img src="https://via.placeholder.com/200x120" class="img-fluid">
            <p class="mt-2">Organic Certified</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= CTA ================= -->
    <section class="bg-success text-white text-center py-5">
      <div class="container">
        <h2 class="fw-bold">Committed to True Wellness</h2>
        <p class="lead mt-2">
          Pure • Natural • Effective
        </p>
        <a href="#" class="btn btn-light btn-lg mt-2">Contact Us</a>
      </div>
    </section>
  @endif
  

  <main>
    @yield('content')
  </main>


  @include('blocks.footer')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/themes/greenbs/assets/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/themes/greenbs/assets/js/main.js"></script>

</body>
</html>