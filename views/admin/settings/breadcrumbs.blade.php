@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Breadcrumb Settings</h1>

    @if(isset($_GET['status']) && $_GET['status'] === 'updated')
        <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2">
            Breadcrumb settings updated successfully.
        </div>
    @endif

    @if(isset($_GET['status']) && $_GET['status'] === 'settings-missing')
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
            Settings table not found. Please create the settings table first.
        </div>
    @endif

    <form method="POST" action="/admin/settings/breadcrumbs" class="bg-white border rounded-lg p-4 space-y-4">

        <div class="form-group">
            <label class="block text-sm font-semibold mb-2">
                <input type="checkbox" name="enabled" value="1" class="mr-2"
                    {{ ($defaults['enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                Enable Breadcrumbs
            </label>
        </div>

        <div class="form-group">
            <label for="type" class="block text-sm font-semibold mb-2">Breadcrumb Type</label>
            <div class="relative">
                <select name="type" id="type" class="w-full border rounded px-3 py-2 appearance-none">
                    <option value="auto" {{ ($defaults['type'] ?? 'auto') === 'auto' ? 'selected' : '' }}>Auto (Best available source)</option>
                    <option value="page" {{ ($defaults['type'] ?? 'auto') === 'page' ? 'selected' : '' }}>Page Hierarchy</option>
                    <option value="category" {{ ($defaults['type'] ?? 'auto') === 'category' ? 'selected' : '' }}>Category Hierarchy</option>
                    <option value="menu" {{ ($defaults['type'] ?? 'auto') === 'menu' ? 'selected' : '' }}>Menu Path</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">"Auto" will try page hierarchy, then category hierarchy.</p>
        </div>

        <div class="form-group">
            <label class="block text-sm font-semibold mb-2">
                <input type="checkbox" name="show_home" value="1" class="mr-2"
                    {{ ($defaults['show_home'] ?? '0') === '1' ? 'checked' : '' }}>
                Show Home Link
            </label>
            <p class="text-xs text-gray-500 mt-2">Display a "Home" link as the first item in the breadcrumbs.</p>
        </div>

        <div class="form-group">
            <label for="home_label" class="block text-sm font-semibold mb-2">Home Label</label>
            <input
                type="text"
                id="home_label"
                name="home_label"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['home_label'] ?? 'Home' }}"
                placeholder="Home"
            >
            <p class="text-xs text-gray-500 mt-2">Text for the home link (e.g., "Home", "Start").</p>
        </div>

        <div class="form-group">
            <label for="separator" class="block text-sm font-semibold mb-2">Separator Style</label>
            <input
                type="text"
                id="separator"
                name="separator"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['separator'] ?? '/' }}"
                placeholder="/"
            >
            <p class="text-xs text-gray-500 mt-2">Character or string to use as a separator (e.g., "/", ">", "::").</p>
        </div>

        <div class="form-group">
            <label class="block text-sm font-semibold mb-2">
                <input type="checkbox" name="schema" value="1" class="mr-2"
                    {{ ($defaults['schema'] ?? '0') === '1' ? 'checked' : '' }}>
                Enable Schema (JSON-LD)
            </label>
            <p class="text-xs text-gray-500 mt-2">Output breadcrumb schema.org JSON-LD for SEO.</p>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
            Save Settings
        </button>

    </form>
@endsection