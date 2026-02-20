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

    {{-- Custom Fields Section --}}
    <div class="mt-10">
        <h2 class="text-xl font-semibold mb-2">Custom Fields</h2>
        <form method="POST" action="/admin/content-types/{{ $type->id }}/fields" class="space-y-4">
            <table class="w-full border text-sm mb-4">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border">Label</th>
                        <th class="p-2 border">Name</th>
                        <th class="p-2 border">Type</th>
                        <th class="p-2 border">Required</th>
                        <th class="p-2 border">Sort</th>
                        <th class="p-2 border">Delete</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($fields as $i => $field)
                    <tr>
                        <td class="border p-1"><input type="text" name="fields[{{ $field->id }}][label]" value="{{ $field->label }}" class="border rounded px-2 py-1 w-full"></td>
                        <td class="border p-1"><input type="text" name="fields[{{ $field->id }}][name]" value="{{ $field->name }}" class="border rounded px-2 py-1 w-full"></td>
                        <td class="border p-1">
                            <select name="fields[{{ $field->id }}][type]" class="border rounded px-2 py-1 w-full">
                                <option value="text" {{ $field->type == 'text' ? 'selected' : '' }}>Text</option>
                                <option value="textarea" {{ $field->type == 'textarea' ? 'selected' : '' }}>Textarea</option>
                                <option value="select" {{ $field->type == 'select' ? 'selected' : '' }}>Select</option>
                                <option value="checkbox" {{ $field->type == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                <option value="radio" {{ $field->type == 'radio' ? 'selected' : '' }}>Radio</option>
                                <option value="number" {{ $field->type == 'number' ? 'selected' : '' }}>Number</option>
                                <option value="date" {{ $field->type == 'date' ? 'selected' : '' }}>Date</option>
                            </select>
                        </td>
                        <td class="border p-1 text-center"><input type="checkbox" name="fields[{{ $field->id }}][required]" value="1" {{ $field->required ? 'checked' : '' }}></td>
                        <td class="border p-1"><input type="number" name="fields[{{ $field->id }}][sort_order]" value="{{ $field->sort_order }}" class="border rounded px-2 py-1 w-16"></td>
                        <td class="border p-1 text-center"><input type="checkbox" name="fields[{{ $field->id }}][delete]"></td>
                    </tr>
                @endforeach
                {{-- New Field Row --}}
                <tr>
                    <td class="border p-1"><input type="text" name="new_field[label]" class="border rounded px-2 py-1 w-full" placeholder="Label"></td>
                    <td class="border p-1"><input type="text" name="new_field[name]" class="border rounded px-2 py-1 w-full" placeholder="name"></td>
                    <td class="border p-1">
                        <select name="new_field[type]" class="border rounded px-2 py-1 w-full">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Select</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                        </select>
                    </td>
                    <td class="border p-1 text-center"><input type="checkbox" name="new_field[required]" value="1"></td>
                    <td class="border p-1"><input type="number" name="new_field[sort_order]" class="border rounded px-2 py-1 w-16" value="0"></td>
                    <td class="border p-1"></td>
                </tr>
                </tbody>
            </table>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save Fields</button>
        </form>
    </div>
</div>
@endsection
