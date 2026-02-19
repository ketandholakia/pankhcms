{{-- Empty file for products block --}}<div class="container-fluid py-5">
<div class="container">

<div class="text-center mb-5">
<h6 class="text-primary text-uppercase">Products</h6>
<h1 class="display-5">Our Fresh Products</h1>
</div>

<div class="owl-carousel product-carousel px-5">

@foreach($products as $product)

<div class="pb-5">
<div class="product-item bg-white text-center">

<img class="img-fluid mb-4"
src="{{ $product->featured_image }}">

<h6>{{ $product->title }}</h6>

<h5 class="text-primary">
₹{{ $product->price ?? '' }}
</h5>

</div>
</div>

@endforeach

</div>
</div>
</div>
