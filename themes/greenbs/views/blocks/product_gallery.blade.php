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
<div class="container py-5">
    <h2 class="mb-4">Product Gallery</h2>
    <div class="row">
        @foreach($products as $product)
            @php
                $imageUrl = (string) (
                    $product->featured_image
                    ?: $product->seo_image
                    ?: $product->og_image
                    ?: $product->customField('featured_image', '')
                    ?: $product->customField('image', '')
                    ?: $product->customField('product_image', '')
                    ?: $product->customField('thumbnail', '')
                );

                $imageUrl = trim($imageUrl);
                if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl) && !str_starts_with($imageUrl, '/')) {
                    $imageUrl = '/' . ltrim($imageUrl, '/');
                }
            @endphp
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    @if($imageUrl !== '')
                        <img src="{{ $imageUrl }}" class="card-img-top" alt="{{ $product->title }}">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <span class="text-muted">No image</span>
                        </div>
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
