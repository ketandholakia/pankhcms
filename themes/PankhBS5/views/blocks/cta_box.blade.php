@php
    $title = $block['title'] ?? 'Ready to get started?';
    $text = $block['text'] ?? '';
    $buttonText = $block['button_text'] ?? 'Learn More';
    $buttonUrl = $block['button_url'] ?? '#';
@endphp
<section class="section-gap py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="h3 mb-3">{{ $title }}</h2>
        @if($text !== '')
            <p class="lead mb-4">{{ $text }}</p>
        @endif
        <a href="{{ $buttonUrl }}" class="btn btn-light px-4">{{ $buttonText }}</a>
    </div>
</section>
