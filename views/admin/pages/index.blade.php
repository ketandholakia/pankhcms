@extends('layouts.admin')

@section('content')
    @if(isset($_GET['saved']) && $_GET['saved'] === '1')
        <div id="page-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow">
            Saved successfully.
        </div>
        <script>
        setTimeout(() => {
            const notice = document.getElementById('page-notice');
            if (notice) {
                notice.classList.add('hidden');
            }
        }, 2000);
        </script>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i data-lucide="file-text"></i>
            Manage Pages
        </h1>

        <div class="flex items-center gap-3">
            <a href="/admin/pages/create" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded text-sm">
                Create Page
            </a>

            <form method="GET" action="/admin/pages" class="flex items-center gap-2">
                <label for="type" class="text-sm font-medium text-gray-700">Type</label>
                <select id="type" name="type" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($selectedType ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    @foreach($types as $type)
                        <option value="{{ $type->slug }}" {{ ($selectedType ?? 'all') === $type->slug ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Title
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Slug
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Categories
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Tags
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">{{ $page->title }}</p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">{{ $page->slug }}</p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">{{ $page->contentType->name ?? ucfirst($page->type ?? 'page') }}</p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">
                                {{ $page->categories->pluck('name')->implode(', ') }}
                            </p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">
                                {{ $page->tags->pluck('name')->implode(', ') }}
                            </p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-right">
                            <a href="/admin/pages/{{ $page->id }}/edit" class="bg-yellow-400 text-white px-3 py-1 rounded mr-2">Edit</a>
                            <form action="/admin/pages/{{ $page->id }}/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">No pages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection