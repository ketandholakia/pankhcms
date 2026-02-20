<header class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand" href="/">{{ $site_name ?? 'Site' }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @foreach($menu_items ?? [] as $item)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ $item['url'] ?? '#' }}">{{ $item['title'] }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</header>
