@php
    $heading = (string) ($block['heading'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $buttonText = (string) ($block['button_text'] ?? '');
    $buttonLink = (string) ($block['button_link'] ?? '');
@endphp
<section class="pb-block pb-block-cta has-background-light">
    <div class="container has-text-centered">
        @if($heading !== '')
            <h2 class="title is-3">{{ $heading }}</h2>
        @endif
        @if($text !== '')
            <div class="content">{!! sanitize_html($text) !!}</div>
        @endif
        @if($buttonText !== '' && $buttonLink !== '')
            <a class="button is-primary" href="{{ preg_match('#^https?://#i', $buttonLink) ? $buttonLink : url($buttonLink) }}">{{ $buttonText }}</a>
        @endif
    </div>
</section>
