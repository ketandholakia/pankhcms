@php
use App\Models\SliderImage;
$sliderImages = SliderImage::where('active', 1)->orderBy('sort_order')->get();
@endphp

@if($sliderImages->count())
<div class="container-fluid px-0">
    <div id="pankhbs5-header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-inner">
            @foreach($sliderImages as $i => $slide)
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    <img class="d-block w-100 card-media" src="{{ $slide->image_path }}" alt="{{ $slide->caption }}">
                    @if($slide->caption || $slide->link)
                        <div class="carousel-caption d-flex flex-column justify-content-center h-100">
                            @if($slide->caption)
                                <h2>{{ $slide->caption }}</h2>
                            @endif
                            @if($slide->link)
                                <p><a href="{{ $slide->link }}" class="btn btn-primary" target="_blank" rel="noopener">Learn More</a></p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#pankhbs5-header-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#pankhbs5-header-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>
@endif
