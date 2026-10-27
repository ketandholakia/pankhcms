@php
    // lines format: "quote | author | role" — one entry per line
    $lines = preg_split('/
||
/', (string) ($block['lines'] ?? '')) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') { continue; }
        $parts = array_map('trim', explode('|', $line));
        $items[] = ['quote' => $parts[0] ?? '', 'author' => $parts[1] ?? '', 'role' => $parts[2] ?? ''];
    }
@endphp
@if(count($items))
<section class="pb-block pb-block-testimonials">
    <div class="container">
        <div class="columns is-multiline">
            @foreach($items as $item)
                <figure class="column is-4 testimonial">
                    <blockquote class="card-content">{{ $item['quote'] }}</blockquote>
                    <figcaption class="testimonial-author">
                        {{ $item['author'] }}@if($item['role'] !== '') — {{ $item['role'] }}@endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
