<section class="py-4">
    <div class="container">
        <h2>{{ $block['data']['title'] ?? '' }}</h2>
        <div class="row">
            @foreach($block['data']['members'] ?? [] as $member)
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <img src="{{ $member['photo'] ?? '' }}" class="card-img-top" alt="{{ $member['name'] }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $member['name'] }}</h5>
                            <p class="card-text">{{ $member['role'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
