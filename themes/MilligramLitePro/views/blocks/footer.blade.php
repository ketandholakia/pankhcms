<footer class="site-footer">
  <div class="container footer-columns">
    <div>
      <h5>{{ $site_name ?? 'Site Name' }}</h5>
      <p>Quality products you can trust.</p>
    </div>

    <div>
      <h5>Links</h5>
      <p><a href="/">Home</a></p>
      <p><a href="/contact">Contact</a></p>
    </div>

    <div>
      <h5>Contact</h5>
      <p>Email: info@example.com</p>
    </div>
  </div>

  <p style="text-align:center;margin-top:2rem;">
    &copy; {{ date('Y') }} {{ $site_name ?? 'Site Name' }}
  </p>
</footer>
