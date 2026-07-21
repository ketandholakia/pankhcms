@extends('layouts.main')

@section('content')
@php
    $heroIntroText = setting('hero_intro_text', 'Transforming ideas into powerful digital solutions with cutting-edge technology.');
    $primaryLabel = setting('hero_primary_cta_label', 'Get Started');
    $primaryUrl = setting('hero_primary_cta_url', '/contact-us');
    $secondaryLabel = setting('hero_secondary_cta_label', 'View Projects');
    $secondaryUrl = setting('hero_secondary_cta_url', '/projects');
    $usableSlides = collect($sliders ?? [])->filter(function ($slide) {
        return !isset($slide->active) || (int) $slide->active === 1;
    })->values();
    $heroImage = optional($usableSlides->first())->image_path ?: '/media/demo/hero1.png';
@endphp

{!! blocks_html('homepage_top') !!}

<section class="hero-slider">
<div class="carousel" data-autoplay="true" data-delay="5000">
    <div class="item">
        <section class="hero is-medium hero-slide slide-1">
            <div class="hero-body">
                <div class="container">
                    <div class="columns is-vcentered">
                        <div class="column is-6">
                            <h1 class="title is-1">
                                {{ setting('site_name') }}
                            </h1>

                            <h2 class="subtitle is-4">
                                {{ setting('site_tagline') }}
                            </h2>

                            <p>{{ $heroIntroText }}</p>

                            <div class="buttons mt-5">
                                <a href="{{ $primaryUrl }}" class="button is-link is-medium">
                                    {{ $primaryLabel }}
                                </a>

                                <a href="{{ $secondaryUrl }}" class="button is-light is-medium">
                                    {{ $secondaryLabel }}
                                </a>
                            </div>
                        </div>

                        <div class="column is-6 has-text-centered">
                            <img src="{{ $heroImage }}" alt="{{ setting('site_name') }}">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @foreach($usableSlides->slice(1) as $index => $slide)
        @php
            $variant = (($index + 2) % 3) + 1;
            $slideTitle = trim((string) ($slide->title ?? $slide->caption ?? 'Featured Work'));
            $slideDescription = trim((string) ($slide->description ?? $slide->caption ?? 'Learn more about this featured highlight.'));
            $slideUrl = trim((string) ($slide->link ?? ''));
        @endphp
        <div class="item">
            <section class="hero is-medium hero-slide slide-{{ $variant }}">
                <div class="hero-body">
                    <div class="container">
                        <div class="columns is-vcentered">
                            <div class="column is-6">
                                <h2 class="title is-1">{{ $slideTitle }}</h2>
                                <p>{{ $slideDescription }}</p>

                                @if($slideUrl !== '')
                                    <div class="buttons mt-5">
                                        <a href="{{ $slideUrl }}" class="button is-link is-medium">Explore More</a>
                                    </div>
                                @endif
                            </div>

                            <div class="column is-6 has-text-centered">
                                <img src="{{ $slide->image_path }}" alt="{{ $slideTitle }}">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endforeach
</div>

</section>

{!! blocks_html('homepage_middle') !!}

{!! render_page_blocks($blocks ?? []) !!}

{!! blocks_html('homepage_bottom') !!}

@endsection
