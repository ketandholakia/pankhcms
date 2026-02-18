@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">SEO Settings</h1>

    @if(isset($_GET['status']) && $_GET['status'] === 'updated')
        <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2">
            SEO defaults updated successfully.
        </div>
    @endif

    @if(isset($_GET['status']) && $_GET['status'] === 'settings-missing')
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
            Settings table not found. Please create the settings table first.
        </div>
    @endif

    <form method="POST" action="/admin/settings/seo" class="bg-white border rounded-lg p-4 space-y-4">
        <div>
            <label for="default_title" class="block text-sm font-semibold mb-2">Default SEO Title</label>
            <input
                type="text"
                id="default_title"
                name="default_title"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['default_title'] }}"
                placeholder="Default title used when page SEO title is empty"
            >
        </div>

        <div>
            <label for="default_description" class="block text-sm font-semibold mb-2">Default SEO Description</label>
            <textarea
                id="default_description"
                name="default_description"
                class="w-full border rounded px-3 py-2"
                rows="4"
                placeholder="Default description used when page SEO description is empty"
            >{{ $defaults['default_description'] }}</textarea>
        </div>

        <div>
            <label for="default_keywords" class="block text-sm font-semibold mb-2">Default SEO Keywords</label>
            <input
                type="text"
                id="default_keywords"
                name="default_keywords"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['default_keywords'] }}"
                placeholder="keyword1, keyword2, keyword3"
            >
            <p class="text-xs text-gray-500 mt-2">Use comma-separated keywords.</p>
        </div>

        <h2 class="text-xl font-bold mt-6 mb-4">Open Graph (Social Sharing) Defaults</h2>
        <div>
            <label for="og_title_default" class="block text-sm font-semibold mb-2">Default Open Graph Title</label>
            <input
                type="text"
                id="og_title_default"
                name="og_title_default"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['og_title_default'] }}"
                placeholder="Default title for social media shares"
            >
            <p class="text-xs text-gray-500 mt-2">Title used for social media sharing if page-specific OG title is empty.</p>
        </div>
        <div>
            <label for="og_description_default" class="block text-sm font-semibold mb-2">Default Open Graph Description</label>
            <textarea
                id="og_description_default"
                name="og_description_default"
                class="w-full border rounded px-3 py-2"
                rows="3"
                placeholder="Default description for social media shares"
            >{{ $defaults['og_description_default'] }}</textarea>
            <p class="text-xs text-gray-500 mt-2">Description used for social media sharing if page-specific OG description is empty.</p>
        </div>
        <div>
            <label for="og_image_default" class="block text-sm font-semibold mb-2">Default Open Graph Image URL</label>
            <input
                type="text"
                id="og_image_default"
                name="og_image_default"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['og_image_default'] }}"
                placeholder="/assets/images/og-default.jpg"
            >
            <p class="text-xs text-gray-500 mt-2">Full URL or relative path to a default image for social sharing (e.g., 1200x630px).</p>
        </div>

        <h2 class="text-xl font-bold mt-6 mb-4">Canonical & Robots Defaults</h2>
        <div>
            <label for="canonical_base" class="block text-sm font-semibold mb-2">Canonical Base URL</label>
            <input
                type="url"
                id="canonical_base"
                name="canonical_base"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['canonical_base'] }}"
                placeholder="https://yourwebsite.com"
            >
            <p class="text-xs text-gray-500 mt-2">Base URL for canonical tags (e.g., https://yourwebsite.com). Used if page-specific canonical URL is empty.</p>
        </div>
        <div>
            <label for="robots_default" class="block text-sm font-semibold mb-2">Default Robots Meta Tag</label>
            <input
                type="text"
                id="robots_default"
                name="robots_default"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['robots_default'] }}"
                placeholder="index, follow"
            >
            <p class="text-xs text-gray-500 mt-2">e.g., 'index, follow', 'noindex, nofollow'. Used if page-specific robots tag is empty.</p>
        </div>

        <h2 class="text-xl font-bold mt-6 mb-4">Twitter Card Defaults</h2>
        <div>
            <label for="twitter_card" class="block text-sm font-semibold mb-2">Default Twitter Card Type</label>
            <input
                type="text"
                id="twitter_card"
                name="twitter_card"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['twitter_card'] }}"
                placeholder="summary_large_image"
            >
            <p class="text-xs text-gray-500 mt-2">e.g., 'summary', 'summary_large_image', 'app', 'player'.</p>
        </div>
        <div>
            <label for="twitter_site" class="block text-sm font-semibold mb-2">Default Twitter Site Handle</label>
            <input
                type="text"
                id="twitter_site"
                name="twitter_site"
                class="w-full border rounded px-3 py-2"
                value="{{ $defaults['twitter_site'] }}"
                placeholder="@yourhandle"
            >
            <p class="text-xs text-gray-500 mt-2">Your Twitter handle (e.g., @pankhcms).</p>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
            Save SEO Defaults
        </button>
    </form>
@endsection
