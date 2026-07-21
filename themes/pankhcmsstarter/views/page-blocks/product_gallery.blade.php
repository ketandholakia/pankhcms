@php
    $products = \App\Models\Page::products()
        ->visible()
        ->get()
        ->filter(function ($p) {
            return $p->isCustomFieldTruthy('show_in_product_gallery');
        })
        ->sortBy(function ($p) {
            $order = $p->customField('gallery_order', null);
            return is_numeric($order) ? [0, (float) $order] : [1, strtolower((string) $p->title)];
        })
        ->values();
@endphp

<section class="section pb-block pb-block-product-gallery">
    <div class="container">
        @if($products->isEmpty())
            <p class="has-text-grey">No products to show yet.</p>
        @else
            <div class="columns is-multiline">
                @foreach($products as $product)
                    <div class="column is-4">
                        <div class="card">
                            @if($product->featured_image)
                                <div class="card-image">
                                    <figure class="image is-4by3">
                                        <img src="{{ $product->featured_image }}" alt="{{ $product->title }}">
                                    </figure>
                                </div>
                            @endif
                            <div class="card-content">
                                <p class="title is-5">
                                    <a href="/{{ ltrim($product->slug, '/') }}">{{ $product->title }}</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
