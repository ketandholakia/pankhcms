<section class="py-5 text-center bg-light">
    <div class="container">
        <h1>{{ $block['data']['title'] ?? '' }}</h1>
        <p class="lead">{{ $block['data']['subtitle'] ?? '' }}</p>
        @if(!empty($block['data']['cta_text']))
            <a href="{{ $block['data']['cta_link'] ?? '#' }}" class="btn btn-primary">{{ $block['data']['cta_text'] }}</a>
        @endif
    </div>
</section>
