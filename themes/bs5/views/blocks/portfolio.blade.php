@php $items = $items ?? []; @endphp

<section class="page-section portfolio" id="portfolio">
<div class="container">

<h2 class="page-section-heading text-center text-uppercase text-secondary mb-0">
Portfolio
</h2>

<div class="divider-custom">
<div class="divider-custom-line"></div>
<div class="divider-custom-icon"><i class="fas fa-star"></i></div>
<div class="divider-custom-line"></div>
</div>

<div class="row justify-content-center">

@foreach($items as $item)
<div class="col-md-6 col-lg-4 mb-5">
    <div class="portfolio-item mx-auto">
        <img class="img-fluid" src="{{ $item['image'] }}" alt="">
    </div>
</div>
@endforeach

</div>
</div>
</section>
