@extends('layouts.admin')

@section('content')
	@php
		$status = $_GET['status'] ?? null;
	@endphp

	<div class="max-w-3xl mx-auto space-y-6">
		<div>
			<h1 class="text-2xl font-bold mb-1">My Profile</h1>
			<p class="text-sm text-gray-600">Update your account details and change your password.</p>
		</div>

		@if($status === 'profile-updated')
			<div class="rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2 text-sm">
				Profile updated successfully.
			</div>
		@elseif($status === 'invalid-email')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				Please provide a valid email address.
			</div>
		@elseif($status === 'email-taken')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				This email address is already in use by another user.
			</div>
		@elseif($status === 'password-updated')
			<div class="rounded border border-green-300 bg-green-50 text-green-800 px-4 py-2 text-sm">
				Password changed successfully.
			</div>
		@elseif($status === 'password-missing')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				Please fill in all password fields.
			</div>
		@elseif($status === 'password-current-invalid')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				Your current password is incorrect.
			</div>
		@elseif($status === 'password-too-short')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				New password must be at least 8 characters long.
			</div>
		@elseif($status === 'password-mismatch')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				New password and confirmation do not match.
			</div>
		@endif

		<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
			<section class="bg-white border rounded-lg p-4 space-y-4">
				<h2 class="text-lg font-semibold">Profile Details</h2>
				<form method="POST" action="/admin/profile" class="space-y-4">
					{!! csrf_field() !!}
					<div>
						<label for="name" class="block text-sm font-semibold mb-1">Name</label>
						<input type="text" id="name" name="name" value="{{ $user->name ?? '' }}" class="w-full border rounded px-3 py-2 text-sm" placeholder="Your name">
					</div>
					<div>
						<label for="email" class="block text-sm font-semibold mb-1">Email <span class="text-red-500">*</span></label>
						<input type="email" id="email" name="email" value="{{ $user->email }}" class="w-full border rounded px-3 py-2 text-sm" required>
					</div>
					<div>
						<button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded">
							<i data-lucide="save"></i>
							Save Profile
						</button>
					</div>
				</form>
			</section>

			<section class="bg-white border rounded-lg p-4 space-y-4">
				<h2 class="text-lg font-semibold">Change Password</h2>
				<form method="POST" action="/admin/profile/password" class="space-y-4">
					{!! csrf_field() !!}
					<div>
						<label for="current_password" class="block text-sm font-semibold mb-1">Current Password <span class="text-red-500">*</span></label>
						<input type="password" id="current_password" name="current_password" class="w-full border rounded px-3 py-2 text-sm" required>
					</div>
					<div>
						<label for="new_password" class="block text-sm font-semibold mb-1">New Password <span class="text-red-500">*</span></label>
						<input type="password" id="new_password" name="new_password" class="w-full border rounded px-3 py-2 text-sm" minlength="8" required>
						<p class="mt-1 text-xs text-gray-500">Minimum 8 characters.</p>
					</div>
					<div>
						<label for="new_password_confirmation" class="block text-sm font-semibold mb-1">Confirm New Password <span class="text-red-500">*</span></label>
						<input type="password" id="new_password_confirmation" name="new_password_confirmation" class="w-full border rounded px-3 py-2 text-sm" minlength="8" required>
					</div>
					<div>
						<button type="submit" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2 rounded">
							<i data-lucide="lock"></i>
							Update Password
						</button>
					</div>
				</form>
			</section>
		</div>
	</div>
@endsection
