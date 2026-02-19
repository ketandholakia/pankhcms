@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Content Types</h1>
        <a href="/admin/content-types/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            New Content Type
        </a>
    </div>

    @if(isset($types) && count($types))
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b text-left">Name</th>
                    <th class="py-2 px-4 border-b text-left">Slug</th>
                    <th class="py-2 px-4 border-b text-left">Categories</th>
                    <th class="py-2 px-4 border-b text-left">Tags</th>
                    <th class="py-2 px-4 border-b text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $type)
                    <tr>
                        <td class="py-2 px-4 border-b">{{ $type->name }}</td>
                        <td class="py-2 px-4 border-b">{{ $type->slug }}</td>
                        <td class="py-2 px-4 border-b">{{ $type->has_categories ? 'Yes' : 'No' }}</td>
                        <td class="py-2 px-4 border-b">{{ $type->has_tags ? 'Yes' : 'No' }}</td>
                        <td class="py-2 px-4 border-b">
                            <div class="flex items-center gap-2">
                                <a href="/admin/content-types/{{ $type->id }}/edit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Edit</a>
                                @if(!$type->is_system)
                                    <form method="POST" action="/admin/content-types/{{ $type->id }}/delete" onsubmit="return confirm('Delete this content type?')">
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Delete</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500">System</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No content types found.</p>
    @endif
</div>
@endsection
