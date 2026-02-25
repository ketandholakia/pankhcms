@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-bold mb-4">Plugin Manager</h1>

@if ($flash)
    <div class="mb-4 px-4 py-2 rounded bg-green-100 text-green-800 border border-green-200">{{ $flash }}</div>
@endif

<form method="POST" action="/admin/plugins/upload" enctype="multipart/form-data" class="mb-6">
    <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
    <label class="block mb-2">Upload Plugin (.zip): <input type="file" name="plugin_zip" required></label>
    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded">Upload</button>
</form>

<div class="overflow-x-auto">
    <table class="min-w-full border">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Description</th>
                <th class="px-4 py-2">Version</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($plugins as $plugin)
            <tr class="border-t">
                <td class="px-4 py-2">{{ $plugin['name'] }}</td>
                <td class="px-4 py-2">{{ $plugin['description'] }}</td>
                <td class="px-4 py-2 text-center">{{ $plugin['version'] }}</td>
                <td class="px-4 py-2 text-center">{{ $plugin['active'] ? 'Active' : 'Inactive' }}</td>
                <td class="px-4 py-2">
                    <form method="POST" action="/admin/plugins/toggle" style="display:inline;">
                        <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
                        <input type="hidden" name="slug" value="{{ $plugin['slug'] }}">
                        <button type="submit" class="px-2 py-1 bg-gray-200 rounded">{{ $plugin['active'] ? 'Deactivate' : 'Activate' }}</button>
                    </form>
                    <form method="POST" action="/admin/plugins/uninstall" style="display:inline; margin-left:8px;" onsubmit="return confirm('Are you sure you want to uninstall this plugin?');">
                        <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf'] ?? '' }}">
                        <input type="hidden" name="slug" value="{{ $plugin['slug'] }}">
                        <button type="submit" class="px-2 py-1 text-red-600">Uninstall</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endsection
