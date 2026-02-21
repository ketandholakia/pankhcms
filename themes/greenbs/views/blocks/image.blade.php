<section class="py-4">
    <div class="container text-center">
        <img src="{{ $block['data']['src'] ?? '' }}" alt="{{ $block['data']['alt'] ?? '' }}" class="img-fluid mb-2">
        @if(!empty($block['data']['caption']))
            <div class="text-muted">{{ $block['data']['caption'] }}</div>
        @endif
    </div>
</section>
