{{-- Both shapes: string text, or array with items => [[question, answer], ...] --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
    $items = $data['items'] ?? [];
@endphp
<section class="block-faq section">
    <div class="container">
        @if($title !== '' && $showTitle)
            <h3 class="title is-3">{{ $title }}</h3>
        @endif

        @if(is_array($items) && count($items))
            @foreach($items as $item)
                <details class="faq-item">
                    <summary>{{ $item['question'] ?? '' }}</summary>
                    <div class="faq-answer">{!! sanitize_html((string) ($item['answer'] ?? '')) !!}</div>
                </details>
            @endforeach
        @elseif($text !== '')
            <div class="content">{!! $text !!}</div>
        @endif
    </div>
</section>
