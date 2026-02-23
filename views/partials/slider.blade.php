@if(!empty($sliders) && $sliders->count() > 0)
<div class="relative w-full overflow-hidden bg-gray-900 mb-6" id="hero-slider">
    {{-- Slides --}}
    <div class="flex transition-transform duration-700 ease-in-out" id="slider-track">
        @foreach($sliders as $slide)
        <div class="min-w-full relative">
            <img
                src="{{ $slide->image_path }}"
                alt="{{ $slide->caption ?? '' }}"
                class="w-full object-cover max-h-[480px]"
            >
            @if(!empty($slide->caption) || !empty($slide->link))
            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white p-4 text-center">
                @if(!empty($slide->caption))
                    <p class="text-xl font-semibold">{{ $slide->caption }}</p>
                @endif
                @if(!empty($slide->link))
                    <a href="{{ $slide->link }}" class="inline-block mt-2 px-4 py-2 bg-white text-gray-900 rounded text-sm font-medium hover:bg-gray-200 transition">Learn More</a>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Prev / Next --}}
    @if($sliders->count() > 1)
    <button onclick="sliderMove(-1)" class="absolute top-1/2 left-3 -translate-y-1/2 bg-black/40 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center text-lg transition" aria-label="Previous">&#8592;</button>
    <button onclick="sliderMove(1)"  class="absolute top-1/2 right-3 -translate-y-1/2 bg-black/40 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center text-lg transition" aria-label="Next">&#8594;</button>

    {{-- Dots --}}
    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2" id="slider-dots">
        @foreach($sliders as $i => $slide)
        <button onclick="sliderGoTo({{ $i }})" class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition slider-dot {{ $i === 0 ? '!bg-white' : '' }}" aria-label="Go to slide {{ $i + 1 }}"></button>
        @endforeach
    </div>
    @endif
</div>

<script>
(function(){
    const track = document.getElementById('slider-track');
    const dots  = document.querySelectorAll('.slider-dot');
    let current = 0;
    const total = {{ $sliders->count() }};
    let timer;

    function goTo(n) {
        current = (n + total) % total;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';
        dots.forEach((d, i) => {
            d.classList.toggle('!bg-white', i === current);
            d.classList.toggle('bg-white/50', i !== current);
        });
    }

    window.sliderMove = function(dir) { clearInterval(timer); goTo(current + dir); startAuto(); };
    window.sliderGoTo = function(n)   { clearInterval(timer); goTo(n); startAuto(); };

    function startAuto() {
        timer = setInterval(function(){ goTo(current + 1); }, 5000);
    }

    startAuto();
})();
</script>
@endif
