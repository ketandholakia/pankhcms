@if(count($tree))
<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
    @foreach($tree as $item)
        @php
            $itemUrl = $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : ($item->url ?? '#');
            $hasChildren = !empty($item->children) && count($item->children) > 0;
        @endphp
        <li class="nav-item {{ $hasChildren ? 'dropdown' : '' }}">
            <a
                href="{{ $itemUrl }}"
                class="nav-link {{ $hasChildren ? 'dropdown-toggle' : '' }}"
                @if($hasChildren)
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                @endif
            >
                {{ $item->title }}
            </a>
            @if($hasChildren)
                <ul class="dropdown-menu">
                    @foreach($item->children as $child)
                        @php
                            $childUrl = $child->page_id ? '/' . optional(App\Models\Page::find($child->page_id))->slug : ($child->url ?? '#');
                        @endphp
                        <li><a class="dropdown-item" href="{{ $childUrl }}">{{ $child->title }}</a></li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
@endif
