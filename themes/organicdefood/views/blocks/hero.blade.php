@php
$slides = $slides ?? [];
@endphp

@if(count($slides))
<div class="container-fluid p-0">
    <div id="header-carousel"
         class="carousel slide carousel-fade"
         data-bs-ride="carousel">

        <div class="carousel-inner">

            @foreach($slides as $i => $slide)
            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">

                <img class="w-100"
                     src="{{ $slide['image'] ?? '' }}">

                <div class="carousel-caption d-flex align-items-center justify-content-center">
                    <div class="text-start p-5" style="max-width:900px">

                        <h3 class="text-white">
                            {{ $slide['subtitle'] ?? '' }}
                        </h3>

                        <h1 class="display-1 text-white mb-md-4">
                            {{ $slide['title'] ?? '' }}
                        </h1>

                        @if(!empty($slide['button']))
                        <a href="{{ $slide['button_link'] ?? '#' }}"
                           class="btn btn-primary py-md-3 px-md-5">
                            {{ $slide['button'] }}
                        </a>
                        @endif

                    </div>
                </div>

            </div>
            @endforeach

        </div>
    </div>
</div>

@else
{{-- Optional placeholder --}}
<div class="alert alert-warning text-center m-5">
    Hero block: No slides configured
</div>
@endif
