@extends('layouts.admin')

@section('content')
<div class="container max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Edit Content Type</h1>

    @if(!empty($errors))
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/admin/content-types/{{ $type->id }}" class="bg-white border border-gray-200 rounded p-6 space-y-4">
        <div>
            <label for="name" class="block mb-1 font-semibold">Name</label>
            <input id="name" name="name" type="text" required class="w-full border px-3 py-2 rounded" value="{{ $input['name'] ?? $type->name }}" {{ $type->is_system ? 'readonly' : '' }}>
        </div>

        <div>
            <label for="slug" class="block mb-1 font-semibold">Slug</label>
            <input id="slug" name="slug" type="text" required class="w-full border px-3 py-2 rounded" value="{{ $input['slug'] ?? $type->slug }}" {{ $type->is_system ? 'readonly' : '' }}>
        </div>

        <div>
            <label for="description" class="block mb-1 font-semibold">Description</label>
            <textarea id="description" name="description" rows="3" class="w-full border px-3 py-2 rounded" {{ $type->is_system ? 'readonly' : '' }}>{{ $input['description'] ?? $type->description }}</textarea>
        </div>

        <div>
            <label for="icon" class="block mb-1 font-semibold">Icon</label>
            <input id="icon" name="icon" type="text" class="w-full border px-3 py-2 rounded" value="{{ $input['icon'] ?? $type->icon }}" {{ $type->is_system ? 'readonly' : '' }}>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="has_categories" value="1" {{ !empty($input['has_categories']) ? 'checked' : '' }} {{ $type->is_system ? 'disabled' : '' }}>
                <span>Enable Categories</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="has_tags" value="1" {{ !empty($input['has_tags']) ? 'checked' : '' }} {{ $type->is_system ? 'disabled' : '' }}>
                <span>Enable Tags</span>
            </label>
        </div>

        <div class="flex items-center gap-2">
            @if(!$type->is_system)
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
            @endif
            <a href="/admin/content-types" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">Back</a>
        </div>

        @if($type->is_system)
            <p class="text-sm text-gray-500">System content types cannot be edited.</p>
        @endif
    </form>
</div>
@endsection
