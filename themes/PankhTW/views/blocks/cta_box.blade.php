@php
    $title = $block['title'] ?? 'Ready to get started?';
    $text = $block['text'] ?? '';
    $buttonText = $block['button_text'] ?? 'Learn More';
    $buttonUrl = $block['button_url'] ?? '#';
@endphp
<section class="rounded-2xl bg-slate-900 px-6 py-10 text-center text-white sm:px-10">
    <h2 class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
    @if($text !== '')
        <p class="mx-auto mt-2 max-w-2xl text-slate-300">{{ $text }}</p>
    @endif
    <a href="{{ $buttonUrl }}" class="pankh-btn mt-5 bg-white text-slate-900 hover:bg-slate-100">{{ $buttonText }}</a>
</section>
