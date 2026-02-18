<section class="hero">
  <div class="container">

    <h1>{{ $hero['title'] ?? $site_title ?? 'PankhCMS' }}</h1>

    @if(!empty($hero['subtitle']))
      <p>{{ $hero['subtitle'] }}</p>
    @endif

  </div>
</section>
