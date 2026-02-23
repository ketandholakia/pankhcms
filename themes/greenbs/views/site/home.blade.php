@extends('layouts.main')

@section('content')

<!-- ================= SLIDER ================= -->
@includeIf(theme_view('blocks.slider_bootstrap'))

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

@endsection
