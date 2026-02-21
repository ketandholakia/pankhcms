<section class="py-4">
    <div class="container">
        <h2>{{ $block['data']['title'] ?? '' }}</h2>
        <div class="row">
            @foreach($block['data']['items'] ?? [] as $item)
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $item['title'] }}</h5>
                            <p class="card-text">{{ $item['text'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
