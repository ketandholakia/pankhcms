<nav class="menu">

@foreach (($menu ?? []) as $item)
  <a href="{{ $item['url'] }}">
    {{ $item['title'] }}
  </a>
@endforeach

</nav>
