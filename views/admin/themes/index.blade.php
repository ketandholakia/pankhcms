@extends('layouts.admin')

@section('content')
    <div class="p-6 bg-gray-100 min-h-screen">

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT COLUMN — Theme Settings -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm border p-6">

            <h2 class="text-lg font-semibold mb-4">Theme Settings</h2>

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
                <form method="POST" action="/admin/themes">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Active Theme</label>
                        <select name="theme" id="theme" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none mb-4">
                            @foreach($themes as $theme)
                                <option value="{{ $theme['slug'] }}" {{ $activeTheme === $theme['slug'] ? 'selected' : '' }}>
                                    {{ $theme['name'] }} ({{ $theme['slug'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-start gap-2 mb-4">
                            <input type="checkbox" id="show_theme_credit" name="show_theme_credit" value="1" {{ (isset($showThemeCredit) && $showThemeCredit === '1') ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm">Display theme designer/author in footer</span>
                        </label>
                    </div>

                    {!! csrf_field() !!}
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">Save Theme Settings</button>
                </form>
            @endif

          </div>
        </div>


        <!-- RIGHT COLUMN — Theme List -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border overflow-hidden">

            <div class="px-6 py-4 border-b flex items-center justify-between">
              <h2 class="text-lg font-semibold">Available Themes</h2>
              <span class="text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ count($themes) }} Installed</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                  <tr>
                    <th class="text-left px-6 py-3">Name</th>
                    <th class="text-left px-6 py-3">Slug</th>
                    <th class="text-left px-6 py-3">Version</th>
                    <th class="text-left px-6 py-3">Author</th>
                    <th class="text-left px-6 py-3">Description</th>
                  </tr>
                </thead>

                <tbody class="divide-y">
                  @foreach($themes as $theme)
                    @php $isActive = $activeTheme === $theme['slug']; @endphp
                    <tr class="hover:bg-gray-50 {{ $isActive ? 'bg-blue-50' : '' }}">
                      <td class="px-6 py-4 font-medium {{ $isActive ? 'text-blue-700 font-semibold' : '' }}">
                        @if($isActive)
                          ✓ 
                        @endif
                        {{ $theme['name'] }}
                      </td>
                      <td class="px-6 py-4">{{ $theme['slug'] }}</td>
                      <td class="px-6 py-4">{{ $theme['version'] }}</td>
                      <td class="px-6 py-4">{{ $theme['author'] }}</td>
                      <td class="px-6 py-4 {{ $isActive ? 'text-blue-700' : 'text-gray-600' }}">{{ $theme['description'] }}</td>
                    </tr>
                  @endforeach
                </tbody>

              </table>
            </div>

          </div>
        </div>

      </div>
    </div>
@endsection
