@if(!empty($tree) && count($tree))
    @foreach($tree as $item)
        @php
            $pageId = $item->page_id ?? null;
            $url = '#';
            if (!empty($pageId)) {
                $page = App\Models\Page::find($pageId);
                if ($page && !empty($page->slug)) {
                    $url = '/' . ltrim($page->slug, '/');
                }
            } elseif (!empty($item->url)) {
                $url = $item->url;
            }

            $children = $item->children ?? null;
            $hasChildren = is_countable($children) ? (count($children) > 0) : false;
        @endphp

        @if($hasChildren)
            <div class="navbar-item has-dropdown is-hoverable">
                <a class="navbar-link" href="{{ $url }}">{{ $item->title }}</a>
                <div class="navbar-dropdown">
                    @foreach($item->children as $child)
                        @php
                            $childPageId = $child->page_id ?? null;
                            $childUrl = '#';
                            if (!empty($childPageId)) {
                                $childPage = App\Models\Page::find($childPageId);
                                if ($childPage && !empty($childPage->slug)) {
                                    $childUrl = '/' . ltrim($childPage->slug, '/');
                                }
                            } elseif (!empty($child->url)) {
                                $childUrl = $child->url;
                            }
                        @endphp
                        <a class="navbar-item" href="{{ $childUrl }}">{{ $child->title }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a class="navbar-item" href="{{ $url }}">{{ $item->title }}</a>
        @endif
    @endforeach
@endif
