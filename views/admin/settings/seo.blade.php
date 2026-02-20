@extends('layouts.admin')

@section('content')
        <div class="w-full p-8 bg-gray-50 min-h-screen">
            <form method="POST" action="/admin/settings/seo" class="bg-white border rounded-lg p-6 space-y-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">SEO Settings</h1>
                        <p class="text-sm text-gray-500">Manage your site's SEO defaults and social meta tags</p>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded shadow-sm">Save SEO Defaults</button>
                </div>

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

                <!-- ================= SEO Defaults ================= -->
                <div>
                    <h2 class="text-xl font-bold mb-4">SEO Defaults</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="default_title" class="text-sm font-semibold">Default SEO Title</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Used when a page does not have its own SEO title.
                                    </div>
                                </div>
                            </div>
                            <input type="text" id="default_title" name="default_title" class="w-full border rounded px-3 py-2" value="{{ $defaults['default_title'] }}" placeholder="Used when page title is empty">
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="default_keywords" class="text-sm font-semibold">Default SEO Keywords</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Comma-separated keywords for all pages.
                                    </div>
                                </div>
                            </div>
                            <input type="text" id="default_keywords" name="default_keywords" class="w-full border rounded px-3 py-2" value="{{ $defaults['default_keywords'] }}" placeholder="keyword1, keyword2">
                            <p class="text-xs text-gray-500 mt-1">Comma-separated</p>
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-2 mb-2">
                                <label for="default_description" class="text-sm font-semibold">Default SEO Description</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-72 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Used when a page has no custom description. Recommended length: 150–160 characters.
                                    </div>
                                </div>
                            </div>
                            <textarea id="default_description" name="default_description" rows="3" class="w-full border rounded px-3 py-2" placeholder="Used when page description is empty">{{ $defaults['default_description'] }}</textarea>
                        </div>
                    </div>
                </div>
                <!-- ================= Open Graph ================= -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Open Graph Defaults</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="og_title_default" class="text-sm font-semibold">OG Title</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Social media title (Open Graph title).
                                    </div>
                                </div>
                            </div>
                            <input type="text" name="og_title_default" class="w-full border rounded px-3 py-2" value="{{ $defaults['og_title_default'] }}" placeholder="Social media title">
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="og_image_default" class="text-sm font-semibold">OG Image URL</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Default image for social sharing (1200x630px recommended).
                                    </div>
                                </div>
                            </div>
                            <input type="text" name="og_image_default" class="w-full border rounded px-3 py-2" value="{{ $defaults['og_image_default'] }}" placeholder="/assets/images/og-default.jpg">
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-2 mb-2">
                                <label for="og_description_default" class="text-sm font-semibold">OG Description</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-72 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Social share description. Used if page-specific OG description is empty.
                                    </div>
                                </div>
                            </div>
                            <textarea name="og_description_default" rows="3" class="w-full border rounded px-3 py-2" placeholder="Social share description">{{ $defaults['og_description_default'] }}</textarea>
                        </div>
                    </div>
                </div>
                <!-- ================= Canonical & Robots ================= -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Canonical & Robots</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="canonical_base" class="text-sm font-semibold">Canonical Base URL</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Base URL for canonical tags (e.g., https://yourwebsite.com).
                                    </div>
                                </div>
                            </div>
                            <input type="url" name="canonical_base" class="w-full border rounded px-3 py-2" value="{{ $defaults['canonical_base'] }}" placeholder="https://yourwebsite.com">
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="robots_default" class="text-sm font-semibold">Robots Meta</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        e.g., 'index, follow', 'noindex, nofollow'. Used if page-specific robots tag is empty.
                                    </div>
                                </div>
                            </div>
                            <input type="text" name="robots_default" class="w-full border rounded px-3 py-2" value="{{ $defaults['robots_default'] }}" placeholder="index, follow">
                        </div>
                    </div>
                </div>
                <!-- ================= Twitter ================= -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Twitter Card Defaults</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="twitter_card" class="text-sm font-semibold">Card Type</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        e.g., 'summary', 'summary_large_image', 'app', 'player'.
                                    </div>
                                </div>
                            </div>
                            <input type="text" name="twitter_card" class="w-full border rounded px-3 py-2" value="{{ $defaults['twitter_card'] }}" placeholder="summary_large_image">
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <label for="twitter_site" class="text-sm font-semibold">Twitter Handle</label>
                                <div class="relative group">
                                    <i data-lucide="info" class="w-4 h-4 text-gray-400 cursor-help"></i>
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block w-64 p-2 text-xs text-white bg-gray-900 rounded shadow-lg">
                                        Your Twitter handle (e.g., @pankhcms).
                                    </div>
                                </div>
                            </div>
                            <input type="text" name="twitter_site" class="w-full border rounded px-3 py-2" value="{{ $defaults['twitter_site'] }}" placeholder="@yourhandle">
                        </div>
                    </div>
                </div>
                <!-- ================= Submit ================= -->
                <!-- Button moved to top -->
            </form>
        </div>
@endsection
