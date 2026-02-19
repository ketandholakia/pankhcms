<ul class="navbar-nav ms-auto">

@foreach($items as $item)

@php
    $url = $item->url ?: '/' . ($item->page->slug ?? '');
    if ($url === '/home') $url = '/';
@endphp

<li class="nav-item">
    <a class="nav-link" href="{{ $url }}">
        {{ $item->title }}
    </a>
</li>

@endforeach

</ul>
