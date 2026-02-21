<section class="py-4 bg-white">
    <div class="container">
        <h2>{{ $block['data']['title'] ?? '' }}</h2>
        <div class="row">
            @foreach($block['data']['items'] ?? [] as $item)
                <div class="col-md-4 mb-3">
                    <blockquote class="blockquote">
                        <p>{{ $item['text'] }}</p>
                        <footer class="blockquote-footer">{{ $item['name'] }}</footer>
                    </blockquote>
                </div>
            @endforeach
        </div>
    </div>
</section>
