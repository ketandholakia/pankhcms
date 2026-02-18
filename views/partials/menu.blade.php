<ul class="theme-menu-list">

@foreach($items as $item)

<li class="theme-menu-item {{ count($item->children) ? 'has-children' : '' }}">

    <div class="theme-menu-head">

        <a href="{{ $item->url ?: '/' . ($item->page->slug ?? '') }}"
           class="theme-menu-link">

            {{ $item->title }}

            @if(count($item->children))
                <span class="theme-menu-caret">▾</span>
            @endif

        </a>

        @if(count($item->children))
            <button class="theme-menu-toggle">▾</button>
        @endif

    </div>

    @if(count($item->children))
        @include('partials.menu', ['items' => $item->children])
    @endif

</li>

@endforeach

</ul>
