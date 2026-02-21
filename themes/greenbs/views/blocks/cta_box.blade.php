<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2>{{ $block['data']['title'] ?? '' }}</h2>
        <p>{{ $block['data']['text'] ?? '' }}</p>
        @if(!empty($block['data']['button_text']))
            <a href="{{ $block['data']['button_link'] ?? '#' }}" class="btn btn-light">{{ $block['data']['button_text'] }}</a>
        @endif
    </div>
</section>
