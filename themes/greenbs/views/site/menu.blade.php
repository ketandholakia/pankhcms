@if(count($tree))
<ul class="greenbs-menu-list">
    @foreach($tree as $item)
        @php
            $itemUrl = $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : ($item->url ?? '#');
            $hasChildren = !empty($item->children) && count($item->children) > 0;
            $isActive = rtrim(parse_url(request()->getRequestUri(), PHP_URL_PATH), '/') === rtrim($itemUrl, '/');
        @endphp
        <li class="greenbs-menu-item{{ $hasChildren ? ' has-children' : '' }}{{ $isActive ? ' active' : '' }}">
            <a href="{{ $itemUrl }}" class="greenbs-menu-link{{ $isActive ? ' active' : '' }}">
                {{ $item->title }}
            </a>
            @if($hasChildren)
                <ul class="greenbs-submenu">
                    @foreach($item->children as $child)
                        @php
                            $childUrl = $child->page_id ? '/' . optional(App\Models\Page::find($child->page_id))->slug : ($child->url ?? '#');
                            $isChildActive = rtrim(parse_url(request()->getRequestUri(), PHP_URL_PATH), '/') === rtrim($childUrl, '/');
                        @endphp
                        <li class="greenbs-submenu-item{{ $isChildActive ? ' active' : '' }}">
                            <a class="greenbs-submenu-link{{ $isChildActive ? ' active' : '' }}" href="{{ $childUrl }}">{{ $child->title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
@endif
