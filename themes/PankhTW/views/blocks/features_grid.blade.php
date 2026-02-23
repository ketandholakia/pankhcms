@php
    $title = $block['title'] ?? 'Features';
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
@endphp
<section class="space-y-4">
    <h2 class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($items as $item)
            <article class="pankh-card p-5">
                <h3 class="text-lg font-semibold">{{ $item['title'] ?? 'Feature' }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $item['text'] ?? '' }}</p>
            </article>
        @empty
            <p class="text-sm text-slate-500">No features configured.</p>
        @endforelse
    </div>
</section>
