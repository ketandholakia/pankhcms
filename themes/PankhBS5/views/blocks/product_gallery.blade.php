@php
use App\Models\Page;
$products = Page::products()
    ->published()
    ->get()
    ->filter(fn ($product) => $product->isCustomFieldTruthy('show_in_product_gallery'))
    ->sort(function ($a, $b) {
        $aOrder = (int) ($a->customField('gallery_order', 999999));
        $bOrder = (int) ($b->customField('gallery_order', 999999));

        if ($aOrder === $bOrder) {
            return strcasecmp((string) $a->title, (string) $b->title);
        }

        return $aOrder <=> $bOrder;
    })
    ->values();
@endphp

@if($products->count())
<section class="section-gap">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Product Gallery</h2>
        </div>
        <div class="row g-4">
            @foreach($products as $product)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        @if($product->featured_image)
                            <img src="{{ $product->featured_image }}" class="card-img-top card-media" alt="{{ $product->title }}">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h3 class="h5">{{ $product->title }}</h3>
                            @if($product->meta_description)
                                <p class="text-muted">{{ $product->meta_description }}</p>
                            @endif
                            <a href="/{{ $product->slug }}" class="btn btn-outline-primary mt-auto">View Product</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
