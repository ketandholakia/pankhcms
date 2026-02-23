@php
    $title = $block['title'] ?? 'Gallery';
    $images = is_array($block['images'] ?? null) ? $block['images'] : [];
@endphp
<section class="section-gap">
    <div class="container">
        <h2 class="h3 mb-4">{{ $title }}</h2>
        <div class="row g-3">
            @forelse($images as $img)
                <div class="col-6 col-md-4 col-lg-3">
                    <img src="{{ $img['url'] ?? $img }}" alt="{{ $img['alt'] ?? 'Gallery image' }}" class="img-fluid rounded-3 shadow-sm w-100 card-media">
                </div>
            @empty
                <div class="col-12"><p class="text-muted mb-0">No gallery images configured.</p></div>
            @endforelse
        </div>
    </div>
</section>
