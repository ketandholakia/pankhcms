@php
    $title = $block['title'] ?? 'Gallery';
    $images = is_array($block['images'] ?? null) ? $block['images'] : [];
@endphp
<section class="space-y-4">
    <h2 class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
        @forelse($images as $img)
            <img src="{{ $img['url'] ?? $img }}" alt="{{ $img['alt'] ?? 'Gallery image' }}" class="h-40 w-full rounded-xl border border-slate-200 object-cover shadow-sm">
        @empty
            <p class="col-span-full text-sm text-slate-500">No gallery images configured.</p>
        @endforelse
    </div>
</section>
