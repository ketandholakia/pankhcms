<section class="page-section bg-primary text-white mb-0" id="about">
<div class="container">

<h2 class="page-section-heading text-center text-uppercase text-white">
{{ $title ?? 'About' }}
</h2>

<div class="divider-custom divider-light">
<div class="divider-custom-line"></div>
<div class="divider-custom-icon"><i class="fas fa-star"></i></div>
<div class="divider-custom-line"></div>
</div>

<div class="row">
<div class="col-lg-4 ms-auto">
<p class="lead">{{ $text_left ?? '' }}</p>
</div>

<div class="col-lg-4 me-auto">
<p class="lead">{{ $text_right ?? '' }}</p>
</div>
</div>

</div>
</section>
