{{-- Both shapes: string text, or array with images => [[src, alt, caption], ...] --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
    $images = $data['images'] ?? [];
@endphp
<section class="block-gallery section">
    <div class="container">
        @if($title !== '' && $showTitle)
            <h3 class="title is-3">{{ $title }}</h3>
        @endif

        @if(is_array($images) && count($images))
            <div class="columns is-multiline gallery-grid">
                @foreach($images as $image)
                    <figure class="column is-4 gallery-item">
                        <img src="{{ $image['src'] ?? '' }}" alt="{{ $image['alt'] ?? '' }}" loading="lazy" decoding="async">
                        @if(!empty($image['caption']))
                            <figcaption>{{ $image['caption'] }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @elseif($text !== '')
            <div class="content">{!! $text !!}</div>
        @endif
    </div>
</section>
