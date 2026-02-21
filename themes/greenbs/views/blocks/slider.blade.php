@php
use App\Models\SliderImage;
$sliderImages = SliderImage::where('active', 1)->orderBy('sort_order')->get();
@endphp
@if($sliderImages->count())
<style>
    #theme-slider .item {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.12);
    }
    #theme-slider img {
        width: 100%;
        height: 340px;
        object-fit: cover;
        border-radius: 1.25rem;
        display: block;
    }
    #theme-slider .slider-caption {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        background: linear-gradient(0deg, rgba(0,0,0,0.7) 70%, rgba(0,0,0,0.1) 100%);
        color: #fff;
        padding: 1.5rem 2rem 1rem 2rem;
        border-radius: 0 0 1.25rem 1.25rem;
        text-align: left;
    }
    #theme-slider .slider-caption h5 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: #fff;
        text-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    #theme-slider .slider-caption a.btn {
        margin-top: 0.5rem;
        background: #22c55e;
        border: none;
        color: #fff;
        font-weight: 500;
        border-radius: 0.5rem;
        padding: 0.5rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        transition: background 0.2s;
    }
    #theme-slider .slider-caption a.btn:hover {
        background: #16a34a;
        color: #fff;
    }
    @media (max-width: 600px) {
        #theme-slider img { height: 180px; }
        #theme-slider .slider-caption { padding: 0.75rem 1rem 0.5rem 1rem; }
        #theme-slider .slider-caption h5 { font-size: 1rem; }
    }
</style>
<div id="theme-slider" class="owl-carousel owl-theme mb-6">
    @foreach($sliderImages as $slide)
        <div class="item">
            <img src="{{ $slide->image_path }}" alt="{{ $slide->caption }}">
            @if($slide->caption || $slide->link)
                <div class="slider-caption">
                    @if($slide->caption)
                        <h5>{{ $slide->caption }}</h5>
                    @endif
                    @if($slide->link)
                        <a href="{{ $slide->link }}" class="btn btn-success btn-sm" target="_blank">Learn More</a>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
<script>
if (window.jQuery && window.$.fn.owlCarousel) {
    $('#theme-slider').owlCarousel({
        items:1,
        loop:true,
        margin:10,
        nav:true,
        dots:true,
        autoplay:true,
        autoplayTimeout:4000,
        autoplayHoverPause:true
    });
}
</script>
@endif
