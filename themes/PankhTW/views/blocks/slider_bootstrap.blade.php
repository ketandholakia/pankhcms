@php
use App\Models\SliderImage;
$sliderImages = SliderImage::where('active', 1)->orderBy('sort_order')->get();
@endphp

@if($sliderImages->count())
<section class="overflow-hidden border-b border-slate-200 bg-white">
    <div class="pankh-container py-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($sliderImages as $slide)
                <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                    <img src="{{ $slide->image_path }}" alt="{{ $slide->caption }}" class="h-56 w-full object-cover">
                    @if($slide->caption || $slide->link)
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white">
                            @if($slide->caption)
                                <p class="text-sm font-medium">{{ $slide->caption }}</p>
                            @endif
                            @if($slide->link)
                                <a href="{{ $slide->link }}" class="mt-2 inline-flex text-xs underline" target="_blank" rel="noopener">Learn more</a>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
