@php
    $slides = is_array($block['slides'] ?? null) ? $block['slides'] : [];
    $id = 'custom-slider-' . substr(md5(json_encode($slides)), 0, 8);
@endphp
@if(count($slides))
<section class="section-gap py-0">
    <div id="{{ $id }}" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            @foreach($slides as $i => $slide)
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    <img src="{{ $slide['image'] ?? '' }}" class="d-block w-100" alt="{{ $slide['caption'] ?? 'Slide' }}">
                    @if(!empty($slide['caption']))
                        <div class="carousel-caption d-none d-md-block">
                            <h5>{{ $slide['caption'] }}</h5>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#{{ $id }}" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#{{ $id }}" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>
@endif
