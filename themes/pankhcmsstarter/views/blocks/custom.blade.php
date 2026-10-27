{{-- Both shapes; content is always the sanitized text/html --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
@endphp
<section class="block-custom section">
    <div class="container">
        @if($title !== '' && $showTitle)
            <h3 class="title is-3">{{ $title }}</h3>
        @endif
        <div class="content">{!! $text !!}</div>
    </div>
</section>
