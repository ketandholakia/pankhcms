@php
    $title = $block['title'] ?? 'Testimonials';
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
@endphp
<section class="space-y-4">
    <h2 class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($items as $item)
            <article class="pankh-card p-5">
                <p class="text-slate-700">“{{ $item['quote'] ?? '' }}”</p>
                <p class="mt-3 text-sm font-semibold text-slate-900">{{ $item['name'] ?? 'Customer' }}</p>
            </article>
        @empty
            <p class="text-sm text-slate-500">No testimonials configured.</p>
        @endforelse
    </div>
</section>
