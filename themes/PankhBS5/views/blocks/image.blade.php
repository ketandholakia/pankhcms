@php
    $src = $block['src'] ?? ($block['url'] ?? '');
    $alt = $block['alt'] ?? '';
    $caption = $block['caption'] ?? '';
@endphp
@if($src !== '')
<section class="section-gap py-3">
    <div class="container text-center">
        <img src="{{ $src }}" alt="{{ $alt }}" class="img-fluid rounded-3 shadow-sm">
        @if($caption !== '')
            <p class="text-muted mt-2 mb-0">{{ $caption }}</p>
        @endif
    </div>
</section>
@endif
