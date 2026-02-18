<ul class="list-group list-group-flush">

@foreach($items as $item)

<li class="list-group-item px-0 {{ count($item->children) ? 'has-children' : '' }}">

    <a class="text-decoration-none" href="{{ $item->url ?: '/' . ($item->page->slug ?? '') }}">
        {{ $item->title }}
        @if(count($item->children))
            <span class="badge bg-secondary ms-2">{{ count($item->children) }}</span>
        @endif
    </a>

    @if(count($item->children))
        <div class="ms-3 mt-2">
            @include('partials.menu-bootstrap5', ['items' => $item->children])
        </div>
    @endif

</li>

@endforeach

</ul>
