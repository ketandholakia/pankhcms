@php
$title = $title ?? 'Start Bootstrap';
$subtitle = $subtitle ?? 'Graphic Artist - Web Designer - Illustrator';
$image = $image ?? theme_asset('img/avataaars.svg');
@endphp

<header class="masthead bg-primary text-white text-center">
    <div class="container d-flex align-items-center flex-column">

        <img class="masthead-avatar mb-5" src="{{ $image }}" alt="">

        <h1 class="masthead-heading text-uppercase mb-0">
            {{ $title }}
        </h1>

        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="divider-custom-line"></div>
        </div>

        <p class="masthead-subheading font-weight-light mb-0">
            {{ $subtitle }}
        </p>

    </div>
</header>
