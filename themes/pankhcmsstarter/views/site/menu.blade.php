@if(!empty($tree) && count($tree))
    @foreach($tree as $item)
        @php
            // $item->page_slug is eager-loaded in eager_load_menu_page_slugs()
            // (one query for the whole menu) — no per-item Page::find here.
            $url = '#';
            if (!empty($item->page_slug)) {
                $url = '/' . ltrim($item->page_slug, '/');
            } elseif (!empty($item->url)) {
                $url = $item->url;
            }
            $href = preg_match('#^https?://#i', $url) ? $url : url($url);

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
                <a class="navbar-link" href="{{ $href }}">{{ $item->title }}</a>
                <div class="navbar-dropdown">
                    @foreach($item->children as $child)
                        @php
                            $childUrl = '#';
                            if (!empty($child->page_slug)) {
                                $childUrl = '/' . ltrim($child->page_slug, '/');
                            } elseif (!empty($child->url)) {
                                $childUrl = $child->url;
                            }
                            $childHref = preg_match('#^https?://#i', $childUrl) ? $childUrl : url($childUrl);

                            $normalizedChildUrl = preg_match('#^https?://#i', $childUrl)
                                ? (parse_url($childUrl, PHP_URL_PATH) ?: '/')
                                : '/' . ltrim($childUrl, '/');
                            $isChildActive = rtrim($requestPath, '/') === rtrim($normalizedChildUrl, '/');
                        @endphp
                        <a class="navbar-item {{ $isChildActive ? 'is-active' : '' }}" href="{{ $childHref }}">{{ $child->title }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a class="navbar-item {{ $isActive ? 'is-active' : '' }}" href="{{ $href }}">{{ $item->title }}</a>
        @endif
    @endforeach
@endif
