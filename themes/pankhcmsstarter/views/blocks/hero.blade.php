{{-- Both shapes: string text, or array with heading/text/button_link/button_text --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
    $heading = (string) ($data['heading'] ?? '');
    $buttonText = (string) ($data['button_text'] ?? '');
    $buttonLink = (string) ($data['button_link'] ?? '');
@endphp
<section class="block-hero hero is-medium is-primary">
    <div class="hero-body">
        <div class="container">
            @if($heading !== '')
                <h2 class="title is-2">{{ $heading }}</h2>
            @elseif($title !== '' && $showTitle)
                <h2 class="title is-2">{{ $title }}</h2>
            @endif

            @if($text !== '')
                <div class="content">{!! $text !!}</div>
            @endif

            @if($buttonText !== '' && $buttonLink !== '')
                <a class="button is-light"
                   href="{{ preg_match('#^https?://#i', $buttonLink) ? $buttonLink : url($buttonLink) }}">{{ $
