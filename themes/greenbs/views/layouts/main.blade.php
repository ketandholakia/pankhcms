<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')

    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/styles.css') }}">
 
</head>
<body>
    @includeIf(theme_view('blocks.topbar'))
    @includeIf(theme_view('blocks.header'))

    @php
        $editPageId = null;
        if (\App\Core\Auth::check()) {
            if (isset($page) && !empty($page->id)) {
                $editPageId = (int) $page->id;
            } else {
                $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
                if ($requestPath === '/') {
                    try {
                        $homepageId = (int) (\setting('homepage_id') ?: 0);
                        if ($homepageId > 0) {
                            $homePage = \App\Models\Page::find($homepageId);
                            if ($homePage && !empty($homePage->id)) {
                                $editPageId = (int) $homePage->id;
                            }
                        }
                    } catch (\Throwable $e) {
                        $editPageId = null;
                    }
                }
            }
        }
    @endphp

    @if(!empty($editPageId))
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
            <a href="/admin/pages/{{ $editPageId }}/edit" class="btn btn-sm btn-warning shadow-sm">
                Edit This Page
            </a>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @includeIf(theme_view('blocks.footer'))

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ theme_asset('js/theme.js') }}"></script>
</body>
</html>
