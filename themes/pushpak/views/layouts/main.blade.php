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
    @php($menu_items = menu_tree('header'))
    @include('blocks.header')
    <main class="container py-4">
        @yield('content')
    </main>
    <footer class="bg-dark text-light pt-5 pb-3">
  <div class="container">

    <div class="row g-4">

      <!-- Company Info -->
      <div class="col-lg-4 col-md-6">
        <h5 class="fw-bold text-uppercase">Organic Dehydrated Foods Pvt. Ltd.</h5>
        <p class="small">
          Manufacturer of food supplements, herbal oil drops,
          organic dehydrated vegetables and herbal powders.
          Certified with ISO 22000, HACCP, WHO-GMP and US-FDA standards.
        </p>

        <p class="small mb-0">
          ✔ 100% Natural & Organic<br>
          ✔ No Preservatives<br>
          ✔ No Added Sugar
        </p>
      </div>

      <!-- Quick Links -->
      <div class="col-lg-3 col-md-6">
        <h5 class="fw-bold text-uppercase">Quick Links</h5>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Home</a></li>
          <li><a href="#">About Us</a></li>
          <li><a href="#">Company Profile</a></li>
          <li><a href="#">Process</a></li>
          <li><a href="#">Certification</a></li>
          <li><a href="#">Products</a></li>
          <li><a href="#">Inquiry</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="col-lg-3 col-md-6">
        <h5 class="fw-bold text-uppercase">Contact Us</h5>

        <p class="small mb-1">
          📍 Your Factory Address Here
        </p>

        <p class="small mb-1">
          📞 +91 XXXXX XXXXX
        </p>

        <p class="small mb-1">
          ✉ info@yourdomain.com
        </p>

        <p class="small mb-0">
          🌐 www.yourdomain.com
        </p>
      </div>

      <!-- Certifications -->
      <div class="col-lg-2 col-md-6">
        <h5 class="fw-bold text-uppercase">Certifications</h5>

        <p class="small mb-1">ISO 22000:2018</p>
        <p class="small mb-1">HACCP</p>
        <p class="small mb-1">WHO-GMP</p>
        <p class="small mb-1">US-FDA</p>
        <p class="small mb-0">FSSAI Certified</p>
      </div>

    </div>

    <hr class="border-secondary my-4">

    <!-- Bottom Bar -->
    <div class="text-center small">
      © 2026 Organic Dehydrated Foods Pvt. Ltd. All Rights Reserved.
    </div>

  </div>
</footer>
    @include('blocks.footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/themes/pushpak/assets/js/pushpak.js"></script>
</body>
</html>
