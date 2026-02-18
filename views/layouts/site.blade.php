<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($page->meta_title ?? null) ?: ($page->title ?? 'PankhCMS') }}</title>
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">


    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center space-x-8">
            <a href="/" class="font-bold text-lg">PankhCMS</a>
            {!! render_menu('header') !!}
            </div>

            <form action="/search" method="GET" class="flex gap-2">
                <input
                    type="text"
                    name="q"
                    placeholder="Search..."
                    class="border p-2 rounded"
                    required>
                <button class="bg-gray-900 text-white px-4 rounded" type="submit">
                    Search
                </button>
            </form>
        </div>
    </nav>

    <main class="container mx-auto p-4">
        @yield('content')
    </main>

</body>
</html>
