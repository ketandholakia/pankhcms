@php
    use App\Models\Page;
@endphp

@if(count($tree))
<ul class="theme-menu-list">
    @foreach($tree as $item)
        @php
            $itemUrl = $item->page_id ? '/' . optional(Page::find($item->page_id))->slug : ($item->url ?: '#');
            $hasChildren = !empty($item->children);
        @endphp
        <li class="theme-menu-item{{ $hasChildren ? ' has-children' : '' }}">
            <div class="theme-menu-head">
                <a href="{{ $itemUrl }}" class="theme-menu-link">
                    {{ $item->title }}
                    @if($hasChildren)
                        <span class="theme-menu-caret">
                            <svg width="12" height="12" viewBox="0 0 24 24"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
                        </span>
                    @endif
                </a>
                @if($hasChildren)
                    <button type="button" class="theme-menu-toggle" aria-expanded="false" aria-label="Toggle submenu">▾</button>
                @endif
            </div>
            @if($hasChildren)
                <ul class="theme-submenu">
                    @foreach($item->children as $child)
                        @php
                            $childUrl = $child->page_id ? '/' . optional(Page::find($child->page_id))->slug : ($child->url ?: '#');
                        @endphp
                        <li><a href="{{ $childUrl }}" class="theme-submenu-link">{{ $child->title }}</a></li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
@endif
