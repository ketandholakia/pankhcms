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
            $requestPath = parse_url(\Flight::request()->url ?? '/', PHP_URL_PATH) ?: '/';
            $normalizedUrl = preg_match('#^https?://#i', $url)
                ? (parse_url($url, PHP_URL_PATH) ?: '/')
                : '/' . ltrim($url, '/');
            $isActive = rtrim($requestPath, '/') === rtrim($normalizedUrl, '/');
        @endphp

        @if($hasChildren)
            <div class="navbar-item has-dropdown is-hoverable {{ $isActive ? 'is-active' : '' }}">
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

                            $normalizedChildUrl = preg_match('#^https?://#i', $childUrl)
                                ? (parse_url($childUrl, PHP_URL_PATH) ?: '/')
                                : '/' . ltrim($childUrl, '/');
                            $isChildActive = rtrim($requestPath, '/') === rtrim($normalizedChildUrl, '/');
                        @endphp
                        <a class="navbar-item {{ $isChildActive ? 'is-active' : '' }}" href="{{ $childUrl }}">{{ $child->title }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a class="navbar-item {{ $isActive ? 'is-active' : '' }}" href="{{ $url }}">{{ $item->title }}</a>
        @endif
    @endforeach
@endif
