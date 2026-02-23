<section class="cta-box">
  <h3>{{ $title ?? 'Get Started Today' }}</h3>
  <p>{{ $text ?? '' }}</p>

  @if(!empty($button))
    <a class="button" href="{{ $link ?? '#' }}">{{ $button }}</a>
  @endif
</section>
