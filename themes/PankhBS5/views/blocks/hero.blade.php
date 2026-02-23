@php
    $title = $block['title'] ?? '';
    $subtitle = $block['subtitle'] ?? '';
@endphp
<section class="hero-simple bg-primary text-white section-gap d-flex align-items-center">
    <div class="container py-5">
        @if($title !== '')
            <h1 class="display-5 fw-bold mb-3">{{ $title }}</h1>
        @endif
        @if($subtitle !== '')
            <p class="lead mb-0">{{ $subtitle }}</p>
        @endif
    </div>
</section>
