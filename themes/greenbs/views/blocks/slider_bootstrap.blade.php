@php
use App\Models\SliderImage;
$sliderImages = SliderImage::where('active', 1)->orderBy('sort_order')->get();
@endphp
@if($sliderImages->count())
<style>
    #header-carousel img {
        max-height: 340px;
        object-fit: cover;
    }
    @media (max-width: 600px) {
        #header-carousel img { max-height: 180px; }
    }
</style>
<div class="container-fluid p-0">
    <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-inner">
            @foreach($sliderImages as $i => $slide)
                <div class="carousel-item @if($i === 0) active @endif">
                    <img class="w-100" src="{{ $slide->image_path }}" alt="{{ $slide->caption }}">
                    @if($slide->caption || $slide->link)
                        <div class="carousel-caption top-0 bottom-0 start-0 end-0 d-flex flex-column align-items-center justify-content-center">
                            <div class="text-start p-5" style="max-width: 900px;">
                                @if($slide->caption)
                                    <h3 class="text-white">{{ $slide->caption }}</h3>
                                @endif
                                @if($slide->link)
                                    <a href="{{ $slide->link }}" class="btn btn-primary py-md-3 px-md-5 me-3" target="_blank">Learn More</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
        <div class="carousel-indicators">
            @foreach($sliderImages as $i => $slide)
                <button type="button" data-bs-target="#header-carousel" data-bs-slide-to="{{ $i }}" class="@if($i === 0) active @endif" aria-current="@if($i === 0) true @endif" aria-label="Slide {{ $i+1 }}"></button>
            @endforeach
        </div>
    </div>
</div>
@endif
