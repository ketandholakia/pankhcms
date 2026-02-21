@php
use App\Models\Page;
$products = Page::products()->published()->get();
@endphp
@if($products->count())
<div class="container py-5">
    <h2 class="mb-4">Product Gallery</h2>
    <div class="row">
        @foreach($products as $product)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    @if($product->featured_image)
                        <img src="{{ $product->featured_image }}" class="card-img-top" alt="{{ $product->title }}">
                    @endif
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->title }}</h5>
                        @if($product->meta_description)
                            <p class="card-text">{{ $product->meta_description }}</p>
                        @endif
                        <a href="/{{ $product->slug }}" class="btn btn-primary">View Product</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
