<header class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand" href="/">{{ $site_name ?? 'Site' }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            @php
                $headerItems = $menu_items ?? menu_tree('header');
                if (empty($headerItems)) {
                    $headerItems = menu_tree('navbar');
                }
            @endphp
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @foreach($headerItems as $item)
                    @php
                        $title = is_array($item) ? ($item['title'] ?? '') : ($item->title ?? '');
                        $url = is_array($item) ? ($item['url'] ?? '#') : ($item->url ?? '');

                        if (!$url && !is_array($item) && isset($item->page) && !empty($item->page->slug)) {
                            $url = '/' . ltrim($item->page->slug, '/');
                        }

                        if ($url === '/home') {
                            $url = '/';
                        }

                        if (!$url) {
                            $url = '#';
                        }
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link" href="{{ $url }}">{{ $title }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</header>
