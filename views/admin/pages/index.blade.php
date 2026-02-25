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

        <div class="w-full p-8 bg-gray-50 min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="file-text"></i>
                        Pages
                    </h1>
                    <p class="text-sm text-gray-500">Manage and edit your pages</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin/pages/create" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Page
                    </a>
                    <form method="GET" action="/admin/pages" class="flex items-center gap-2">
                      <label for="q" class="sr-only">Search</label>
                      <input id="q" name="q" type="search" placeholder="Search title or slug" value="{{ $search ?? '' }}" class="border rounded px-3 py-2 text-sm" />

                      <label for="type" class="text-sm font-medium text-gray-700">Type</label>
                      <select id="type" name="type" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                        <option value="all" {{ ($selectedType ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                        @foreach($types as $type)
                          <option value="{{ $type->slug }}" {{ ($selectedType ?? 'all') === $type->slug ? 'selected' : '' }}>
                            {{ $type->name }}
                          </option>
                        @endforeach
                      </select>

                      <button type="submit" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded text-sm">Search</button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full">
                <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Categories</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Tags</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @forelse($pages as $page)
                <tr class="hover:bg-gray-50 transition-colors group">
                  <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $page->title }}</td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ $page->slug }}</td>
                  <td class="px-6 py-4 text-sm">{{ $page->contentType->name ?? ucfirst($page->type ?? 'page') }}</td>
                  <td class="px-6 py-4 text-sm">{{ $page->categories->pluck('name')->implode(', ') }}</td>
                  <td class="px-6 py-4 text-sm">{{ $page->tags->pluck('name')->implode(', ') }}</td>
                  <td class="px-6 py-4 text-right space-x-2">
                    <a href="/admin/pages/{{ $page->id }}/edit" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                      <i data-lucide="pen" class="h-4 w-4 mr-1"></i>
                      Edit
                    </a>
                    <span class="text-gray-300">|</span>
                    <form action="/admin/pages/{{ $page->id }}/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this page?');">
                      <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium text-sm">
                        <i data-lucide="trash-2" class="h-4 w-4 mr-1"></i>
                        Delete
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No pages found.</td></tr>
              @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4 bg-white">
          @if(method_exists($pages, 'links'))
            {!! $pages->appends(array_diff_key($_GET ?? [], ['page' => null]))->links() !!}
          @elseif(!empty($paginator) && ($paginator['last'] ?? 0) > 1)
            @php
              $params = $_GET ?? [];
              unset($params['page']);
              $base = '/admin/pages';
            @endphp
            <div class="flex items-center justify-between">
              <div>
                @if($paginator['current'] > 1)
                  @php $prev = array_merge($params, ['page' => $paginator['current'] - 1]); @endphp
                  <a href="{{ $base }}?{{ http_build_query($prev) }}" class="px-3 py-2 bg-gray-100 rounded">&laquo; Previous</a>
                @endif
              </div>
              <div class="text-sm text-gray-600">Page {{ $paginator['current'] }} of {{ $paginator['last'] }}</div>
              <div>
                @if($paginator['current'] < $paginator['last'])
                  @php $next = array_merge($params, ['page' => $paginator['current'] + 1]); @endphp
                  <a href="{{ $base }}?{{ http_build_query($next) }}" class="px-3 py-2 bg-gray-100 rounded">Next &raquo;</a>
                @endif
              </div>
            </div>
          @endif
        </div>
      </div>
@endsection