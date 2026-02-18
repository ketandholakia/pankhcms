@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Theme Settings</h1>

    @if(isset($_GET['status']) && $_GET['status'] === 'updated')
        <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2">
            Active theme updated successfully.
        </div>
    @endif

    @if(isset($_GET['status']) && $_GET['status'] === 'invalid')
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
            Selected theme is invalid.
        </div>
    @endif

    @if(isset($_GET['status']) && $_GET['status'] === 'settings-missing')
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
            Settings table not found. Please create the settings table first.
        </div>
    @endif

    @if(empty($themes))
        <div class="rounded border border-yellow-300 bg-yellow-50 text-yellow-800 px-4 py-3">
            No themes found in the themes folder.
        </div>
    @else
        <form method="POST" action="/admin/themes" class="bg-white border rounded-lg p-4">
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2" for="theme">Active Theme</label>
                <select name="theme" id="theme" class="w-full border rounded px-3 py-2">
                    @foreach($themes as $theme)
                        <option value="{{ $theme['slug'] }}" {{ $activeTheme === $theme['slug'] ? 'selected' : '' }}>
                            {{ $theme['name'] }} ({{ $theme['slug'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                Save Theme
            </button>
        </form>

        <div class="mt-6 bg-white border rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2 border-b">Name</th>
                        <th class="text-left px-4 py-2 border-b">Slug</th>
                        <th class="text-left px-4 py-2 border-b">Version</th>
                        <th class="text-left px-4 py-2 border-b">Author</th>
                        <th class="text-left px-4 py-2 border-b">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($themes as $theme)
                        <tr class="{{ $activeTheme === $theme['slug'] ? 'bg-blue-50' : '' }}">
                            <td class="px-4 py-2 border-b">{{ $theme['name'] }}</td>
                            <td class="px-4 py-2 border-b">{{ $theme['slug'] }}</td>
                            <td class="px-4 py-2 border-b">{{ $theme['version'] }}</td>
                            <td class="px-4 py-2 border-b">{{ $theme['author'] }}</td>
                            <td class="px-4 py-2 border-b">{{ $theme['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
