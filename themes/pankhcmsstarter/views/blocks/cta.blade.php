{{-- Both shapes: string text, or array with heading/text/button_link/button_text --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
    $heading = (string) ($data['heading'] ?? '');
    $buttonText = (string) ($data['button_text'] ?? '');
    $buttonLink = (string) ($data['button_link'] ?? '');
@endphp
<section class="block-cta section has-background-light">
    <div class="container has-text-centered">
        @if($heading !== '')
            <h2 class="title is-3">{{ $heading }}</h2>
        @elseif($title !== '' && $showTitle)
            <h2 class="title is-3">{{ $title }}</h2>
        @endif

        @if($text !== '')
            <div class="content">{!! $text !!}</div>
        @endif

        @if($buttonText !== '' && $buttonLink !== '')
            <a class="button is-primary is-medium"
               href="{{ preg_match('#^https?://#i', $buttonLink) ? $buttonLink : url($buttonLink) }}">{{ $buttonText }}</a>
        @endif
    </div>
</section>
