<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<div class="swiper hero-slider">

  <div class="swiper-wrapper">

    @php $slides = $block['data']['slides'] ?? []; @endphp
    @foreach($slides as $slide)
      <div class="swiper-slide">

        <section class="hero is-medium"
                 style="background:url('{{ $slide['image'] }}') center/cover no-repeat">

          <div class="hero-body has-text-centered"
               style="background:rgba(0,0,0,0.4)">

            <h1 class="title has-text-white">
              {{ $slide['title'] ?? '' }}
            </h1>

            <p class="subtitle has-text-white">
              {{ $slide['subtitle'] ?? '' }}
            </p>

            @if(!empty($slide['button_text']))
              <a href="{{ $slide['button_link'] ?? '#' }}"
                 class="button is-primary mt-4">
                {{ $slide['button_text'] }}
              </a>
            @endif

          </div>

        </section>

      </div>
    @endforeach

  </div>

  <div class="swiper-pagination"></div>
  <div class="swiper-button-prev"></div>
  <div class="swiper-button-next"></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
new Swiper('.hero-slider', {
  loop: true,
  @php
    $autoplay = $block['data']['autoplay'] ?? false;
    $interval = $block['data']['interval'] ?? 5000;
  @endphp
  autoplay: {{ $autoplay ? '{delay:' . $interval . '}' : 'false' }},
  pagination: { el: '.swiper-pagination' },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev'
  }
});
</script>