@extends('layouts.admin')

@section('content')
	<h1 class="text-2xl font-bold mb-4">Settings</h1>

	<form method="POST" action="/admin/settings/update" enctype="multipart/form-data" class="bg-white border rounded-lg p-4 space-y-4">
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
