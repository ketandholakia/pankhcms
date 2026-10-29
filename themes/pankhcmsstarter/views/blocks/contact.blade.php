{{-- Both shapes: string text (sanitized), or array with heading/text --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
@endphp
<section class="block-contact section">
    <div class="container">
        @if($title !== '' && $showTitle)
            <h3 class="title is-3">{{ $title }}</h3>
        @endif
        <div class="content">{!! $text !!}</div>
        <a class="button is-link" href="{{ url('/contact') }}">Contact us</a>
    </div>
</section>
