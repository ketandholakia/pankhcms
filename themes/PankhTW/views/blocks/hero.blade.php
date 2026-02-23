@php
    $title = $block['title'] ?? '';
    $subtitle = $block['subtitle'] ?? '';
@endphp
<section class="overflow-hidden rounded-2xl bg-slate-900 px-6 py-14 text-white sm:px-10">
    @if($title !== '')
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ $title }}</h1>
    @endif
    @if($subtitle !== '')
        <p class="mt-3 max-w-3xl text-slate-300">{{ $subtitle }}</p>
    @endif
</section>
