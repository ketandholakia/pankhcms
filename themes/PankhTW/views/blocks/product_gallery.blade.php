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
<section class="space-y-4">
    <h2 class="text-2xl font-semibold tracking-tight">Product Gallery</h2>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($products as $product)
            <article class="pankh-card overflow-hidden">
                @if($product->featured_image)
                    <img src="{{ $product->featured_image }}" class="h-44 w-full object-cover" alt="{{ $product->title }}">
                @endif
                <div class="space-y-2 p-4">
                    <h3 class="text-lg font-semibold">{{ $product->title }}</h3>
                    @if($product->meta_description)
                        <p class="text-sm text-slate-600">{{ $product->meta_description }}</p>
                    @endif
                    <a href="/{{ $product->slug }}" class="pankh-btn-outline mt-1">View Product</a>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif
