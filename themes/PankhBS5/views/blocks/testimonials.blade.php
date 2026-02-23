@php
    $title = $block['title'] ?? 'Testimonials';
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
@endphp
<section class="section-gap bg-white">
    <div class="container">
        <h2 class="h3 mb-4">{{ $title }}</h2>
        <div class="row g-4">
            @forelse($items as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <p class="mb-3">“{{ $item['quote'] ?? '' }}”</p>
                            <strong>{{ $item['name'] ?? 'Customer' }}</strong>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted mb-0">No testimonials configured.</p></div>
            @endforelse
        </div>
    </div>
</section>
