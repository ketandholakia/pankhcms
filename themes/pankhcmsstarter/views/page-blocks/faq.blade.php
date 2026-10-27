@php
    // lines format: "question | answer" — one entry per line
    $lines = preg_split('/
||
/', (string) ($block['lines'] ?? '')) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') { continue; }
        $parts = array_map('trim', explode('|', $line, 2));
        $items[] = ['question' => $parts[0], 'answer' => $parts[1] ?? ''];
    }
@endphp
@if(count($items))
<section class="pb-block pb-block-faq">
    <div class="container">
        @foreach($items as $item)
            <details class="faq-item">
                <summary>{{ $item['question'] }}</summary>
                <div class="faq-answer">{!! sanitize_html($item['answer']) !!}</div>
            </details>
        @endforeach
    </div>
</section>
@endif
