<section class="py-4">
    <div class="container">
        <div class="row">
            @foreach($block['data']['images'] ?? [] as $img)
                <div class="col-md-4 mb-3">
                    <img src="{{ $img }}" class="img-fluid rounded shadow-sm" alt="Gallery image">
                </div>
            @endforeach
        </div>
    </div>
</section>
