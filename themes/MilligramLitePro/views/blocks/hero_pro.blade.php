<section class="hero-pro">
  <h1>{{ $title ?? 'Welcome' }}</h1>
  <p>{{ $subtitle ?? '' }}</p>

  @if(!empty($button))
    <a class="button" href="{{ $button_link ?? '#' }}">
      {{ $button }}
    </a>
  @endif
</section>
