@php
    $title = $block['title'] ?? 'Features';
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
@endphp
<section class="section-gap">
    <div class="container">
        <h2 class="h3 mb-4">{{ $title }}</h2>
        <div class="row g-4">
            @forelse($items as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">{{ $item['title'] ?? 'Feature' }}</h3>
                            <p class="mb-0">{{ $item['text'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted mb-0">No features configured.</p></div>
            @endforelse
        </div>
    </div>
</section>
