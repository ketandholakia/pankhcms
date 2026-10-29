{{-- Both shapes: string text, or array with items => [[quote, author, role], ...] --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
    $items = $data['items'] ?? [];
@endphp
<section class="block-testimonials section">
    <div class="container">
        @if($title !== '' && $showTitle)
            <h3 class="title is-3">{{ $title }}</h3>
        @endif

        @if(is_array($items) && count($items))
            <div class="columns is-multiline">
                @foreach($items as $item)
                    <figure class="column is-4 testimonial">
                        <blockquote class="card-content">{{ $item['quote'] ?? '' }}</blockquote>
                        <figcaption class="testimonial-author">
                            {{ $item['author'] ?? '' }}@if(!empty($item['role'])) — {{ $item['role'] }}@endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @elseif($text !== '')
            <div class="content">{!! $text !!}</div>
        @endif
    </div>
</section>
