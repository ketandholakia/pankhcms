@php
    use App\Models\Page;
    $products = Page::products()->published()->get();
@endphp

@if($products->count())
<div class="row product-gallery">
    @foreach($products as $product)
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                @if($product->featured_image)
                    <img src="{{ $product->featured_image }}" class="card-img-top" alt="{{ $product->title }}">
                @endif
                <div class="card-body">
                    <h5 class="card-title">{{ $product->title }}</h5>
                    <p class="card-text">{{ $product->content }}</p>
                    <a href="/{{ $product->slug }}" class="btn btn-primary">View Product</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@else
    <p>No products found.</p>
@endif
i 