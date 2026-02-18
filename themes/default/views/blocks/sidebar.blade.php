<aside>

  <div class="card widget">
    <div class="widget-title">About</div>
    <p>{{ $site_description ?? '' }}</p>
  </div>

  @foreach (($widgets ?? []) as $widget)

    <div class="card widget">
      <div class="widget-title">
        {{ $widget['title'] }}
      </div>

      {!! $widget['content'] !!}
    </div>

  @endforeach

</aside>
