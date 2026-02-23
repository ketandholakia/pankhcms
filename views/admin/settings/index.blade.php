@extends('layouts.admin')

@section('content')
	<h1 class="text-2xl font-bold mb-4">Settings</h1>

	<form method="POST" action="/admin/settings/update" enctype="multipart/form-data" class="bg-white border rounded-lg p-4 space-y-4">
		@if(isset($_GET['status']))
			@if($_GET['status'] === 'updated')
				<div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2">
					Settings updated successfully.
				</div>
			@elseif($_GET['status'] === 'settings-missing')
				<div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
					Settings table not found. Please create the settings table first.
				</div>
			@elseif($_GET['status'] === 'logo-upload-failed')
				<div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
					Failed to upload logo. Please try again.
				</div>
			@elseif($_GET['status'] === 'logo-invalid-type')
				<div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
					Invalid logo file type. Allowed types: JPG, JPEG, PNG, GIF, WEBP, SVG.
				</div>
			@elseif($_GET['status'] === 'favicon-upload-failed')
				<div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
					Failed to upload favicon. Please try again.
				</div>
			@elseif($_GET['status'] === 'favicon-invalid-type')
				<div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2">
					Invalid favicon file type. Allowed types: ICO, PNG, SVG.
				</div>
			@endif
		@endif
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<div>
				<label for="maintenance_mode" class="block text-sm font-semibold mb-2">Maintenance Mode</label>
				<input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" {{ !empty($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1' ? 'checked' : '' }}>
				<span class="ml-2 text-sm text-gray-600">Enable maintenance mode for public site</span>
			</div>

			<div>
				<label for="maintenance_message" class="block text-sm font-semibold mb-2">Maintenance Message</label>
				<input type="text" class="w-full border rounded px-3 py-2" id="maintenance_message" name="maintenance_message" value="{{ $settings['maintenance_message'] ?? 'We are upgrading our website. Please check back soon.' }}">
			</div>

			<div>
				<label for="maintenance_allowed_ips" class="block text-sm font-semibold mb-2">Maintenance Allowlist IPs</label>
				<input type="text" class="w-full border rounded px-3 py-2" id="maintenance_allowed_ips" name="maintenance_allowed_ips" value="{{ $settings['maintenance_allowed_ips'] ?? '' }}" placeholder="127.0.0.1, ::1, 192.168.1.10">
				<p class="mt-1 text-xs text-gray-600">Comma-separated IPs that can bypass maintenance mode. Localhost IPs are always allowed.</p>
			</div>

			<div>
				<label for="contact_map_embed_url" class="block text-sm font-semibold mb-2">Contact Page Map Embed URL</label>
				<input type="url" class="w-full border rounded px-3 py-2" id="contact_map_embed_url" name="contact_map_embed_url" value="{{ $settings['contact_map_embed_url'] ?? '' }}" placeholder="https://www.google.com/maps/embed?...">
				<p class="mt-1 text-xs text-gray-600">Paste Google Maps iframe embed URL for the full-width map on contact page.</p>
			</div>

			<div>
				<label for="show_theme_credit" class="block text-sm font-semibold mb-2">Show Theme Designer Credit in Footer</label>
				<input type="checkbox" id="show_theme_credit" name="show_theme_credit" value="1" {{ !empty($settings['show_theme_credit']) && $settings['show_theme_credit'] == '1' ? 'checked' : '' }}>
				<span class="ml-2 text-sm text-gray-600">Display theme designer/author in the footer</span>
			</div>
			<div>
				<label for="site_name" class="block text-sm font-semibold mb-2">Site Name</label>
				<input type="text" class="w-full border rounded px-3 py-2" id="site_name" name="site_name" value="{{ $settings['site_name'] ?? '' }}">
			</div>

			<div>
				<label for="site_tagline" class="block text-sm font-semibold mb-2">Tagline / Site Description</label>
				<input type="text" class="w-full border rounded px-3 py-2" id="site_tagline" name="site_tagline" value="{{ $settings['site_tagline'] ?? '' }}">
			</div>

			<div>
				<label for="site_url" class="block text-sm font-semibold mb-2">Site URL</label>
				<input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" id="site_url" name="site_url" value="{{ $settings['site_url'] ?? $app_url }}" readonly>
			</div>

			<div>
				<label for="admin_email" class="block text-sm font-semibold mb-2">Admin Email</label>
				<input type="email" class="w-full border rounded px-3 py-2" id="admin_email" name="admin_email" value="{{ $settings['admin_email'] ?? '' }}">
			</div>

			<div>
				<label for="logo_upload" class="block text-sm font-semibold mb-2">Logo Upload</label>
				<input type="file" class="w-full border rounded px-3 py-2" id="logo_upload" name="logo_upload" accept="image/*">
				@if(isset($settings['logo_path']) && $settings['logo_path'])
					<img src="{{ $settings['logo_path'] }}" alt="Site Logo" class="mt-2" style="max-height:60px;">
				@endif
			</div>

			<div>
				<label for="favicon_upload" class="block text-sm font-semibold mb-2">Favicon Upload</label>
				<input type="file" class="w-full border rounded px-3 py-2" id="favicon_upload" name="favicon_upload" accept="image/x-icon,image/png,image/svg+xml">
				@if(isset($settings['favicon_path']) && $settings['favicon_path'])
					<img src="{{ $settings['favicon_path'] }}" alt="Favicon" class="mt-2" style="max-height:32px;">
				@endif
			</div>

			<div>
				<label for="default_language" class="block text-sm font-semibold mb-2">Default Language</label>
				<select class="w-full border rounded px-3 py-2" id="default_language" name="default_language">
					<option value="en" {{ ($settings['default_language'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
					<option value="hi" {{ ($settings['default_language'] ?? '') === 'hi' ? 'selected' : '' }}>Hindi</option>
					<option value="es" {{ ($settings['default_language'] ?? '') === 'es' ? 'selected' : '' }}>Spanish</option>
				</select>
			</div>

			<div>
				<label for="timezone" class="block text-sm font-semibold mb-2">Time Zone</label>
				<select class="w-full border rounded px-3 py-2" id="timezone" name="timezone">
					<option value="UTC" {{ ($settings['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : '' }}>UTC</option>
					<option value="Asia/Kolkata" {{ ($settings['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata</option>
					<option value="America/New_York" {{ ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
					<option value="Europe/London" {{ ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
				</select>
			</div>

			<div>
				<label for="date_format" class="block text-sm font-semibold mb-2">Date Format</label>
				<select class="w-full border rounded px-3 py-2" id="date_format" name="date_format">
					<option value="Y-m-d" {{ ($settings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
					<option value="d-m-Y" {{ ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' }}>DD-MM-YYYY</option>
					<option value="m/d/Y" {{ ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
				</select>
			</div>

			<div>
				<label for="sidebar_search_shortcut" class="block text-sm font-semibold mb-2">Sidebar Search Shortcut</label>
				<input type="text" class="w-full border rounded px-3 py-2" id="sidebar_search_shortcut" name="sidebar_search_shortcut" value="{{ $settings['sidebar_search_shortcut'] ?? 'Ctrl+Shift+F' }}" placeholder="e.g. Ctrl+Shift+F">
				<p class="mt-1 text-xs text-gray-600">Set the keyboard shortcut for focusing the sidebar search. Example: Ctrl+Shift+F</p>
			</div>
			<div>
				<label for="time_format" class="block text-sm font-semibold mb-2">Time Format</label>
				<select class="w-full border rounded px-3 py-2" id="time_format" name="time_format">
					<option value="H:i" {{ ($settings['time_format'] ?? 'H:i') === 'H:i' ? 'selected' : '' }}>24-hour (HH:MM)</option>
					<option value="h:i A" {{ ($settings['time_format'] ?? '') === 'h:i A' ? 'selected' : '' }}>12-hour (HH:MM AM/PM)</option>
				</select>
			</div>
		</div>

		<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
			Save Settings
		</button>
	</form>
@endsection
