@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Content Types</h1>
            <p class="text-sm text-gray-500">Manage and edit your content types</p>
        </div>
        <a href="/admin/content-types/create" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add Content Type
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Categories</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Tags</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @if(isset($types) && count($types))
                    @foreach ($types as $type)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $type->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $type->slug }}</td>
                            <td class="px-6 py-4 text-sm">{{ $type->has_categories ? 'Yes' : 'No' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $type->has_tags ? 'Yes' : 'No' }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="/admin/content-types/{{ $type->id }}/edit" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                                    <i data-lucide="pen" class="h-4 w-4 mr-1"></i>
                                    Edit
                                </a>
                                @if(!$type->is_system)
                                    <span class="text-gray-300">|</span>
                                    <form method="POST" action="/admin/content-types/{{ $type->id }}/delete" class="inline" onsubmit="return confirm('Delete this content type?')">
                                        <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium text-sm">
                                            <i data-lucide="trash-2" class="h-4 w-4 mr-1"></i>
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 ml-2">System</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No content types found.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
