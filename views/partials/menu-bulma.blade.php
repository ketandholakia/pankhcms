<ul class="menu-list">

@foreach($items as $item)

<li>
    <a href="{{ $item->url ?: '/' . ($item->page->slug ?? '') }}">
        {{ $item->title }}
    </a>

    @if(count($item->children))
        @include('partials.menu-bulma', ['items' => $item->children])
    @endif

</li>

@endforeach

</ul>
