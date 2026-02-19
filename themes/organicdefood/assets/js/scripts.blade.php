{{-- Back to Top Button --}}
<a href="#" class="btn btn-secondary py-3 fs-4 back-to-top">
    <i class="bi bi-arrow-up"></i>
</a>

{{-- CDN Libraries (OK to keep external) --}}
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- Local Libraries --}}
<script src="{{ \App\Core\Theme::asset('lib/easing/easing.min.js') }}"></script>

<script src="{{ \App\Core\Theme::asset('lib/waypoints/waypoints.min.js') }}"></script>

<script src="{{ \App\Core\Theme::asset('lib/counterup/counterup.min.js') }}"></script>

<script src="{{ \App\Core\Theme::asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>

{{-- Main Theme Script --}}
<script src="{{ \App\Core\Theme::asset('js/main.js') }}"></script>