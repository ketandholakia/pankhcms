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
		@elseif($status === 'username-required')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				Please provide a username.
			</div>
		@elseif($status === 'username-taken')
			<div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-2 text-sm">
				This username is already in use by another user.
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
						<label for="username" class="block text-sm font-semibold mb-1">Username <span class="text-red-500">*</span></label>
						<input type="text" id="username" name="username" value="{{ $user->username ?? '' }}" class="w-full border rounded px-3 py-2 text-sm" placeholder="Your username" required>
						<p class="mt-1 text-xs text-gray-500">You can use this username or your email address to sign in.</p>
					</div>
					<div>
						<label for="name" class="block text-sm font-semibold mb-1">Display Name</label>
						<input type="text" id="name" name="name" value="{{ $user->name ?? '' }}" class="w-full border rounded px-3 py-2 text-sm" placeholder="Your display name">
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

			<section class="bg-white border rounded-lg p-4 space-y-4 md:col-span-2">
				<h2 class="text-lg font-semibold">API Tokens</h2>
				<p class="text-sm text-gray-600">Generate API tokens to authenticate external applications. Pass the token in the <code>Authorization: Bearer &lt;token&gt;</code> header.</p>

				@if(isset($newApiToken) && $newApiToken)
					<div class="rounded border border-yellow-300 bg-yellow-50 text-yellow-800 px-4 py-4 text-sm mb-4">
						<p class="font-bold mb-2">Please copy your new API token now. You will not be able to see it again!</p>
						<code class="block bg-white border px-3 py-2 rounded text-lg">{{ $newApiToken }}</code>
					</div>
				@endif

				@if(isset($apiTokens) && count($apiTokens) > 0)
					<div class="border rounded overflow-hidden mb-4">
						<table class="w-full text-left text-sm">
							<thead class="bg-gray-50 border-b">
								<tr>
									<th class="px-4 py-2 font-medium">Name</th>
									<th class="px-4 py-2 font-medium">Last Used</th>
									<th class="px-4 py-2 font-medium">Created</th>
									<th class="px-4 py-2 font-medium text-right">Action</th>
								</tr>
							</thead>
							<tbody class="divide-y">
								@foreach($apiTokens as $token)
								<tr>
									<td class="px-4 py-2">{{ $token->name }}</td>
									<td class="px-4 py-2">{{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}</td>
									<td class="px-4 py-2">{{ $token->created_at->format('Y-m-d H:i') }}</td>
									<td class="px-4 py-2 text-right">
										<form method="POST" action="/admin/profile/api-tokens/{{ $token->id }}/revoke" onsubmit="return confirm('Are you sure you want to revoke this token?');">
											{!! csrf_field() !!}
											<button type="submit" class="text-red-600 hover:text-red-800">Revoke</button>
										</form>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				@else
					<p class="text-sm text-gray-500 mb-4">You have not created any API tokens yet.</p>
				@endif

				<form method="POST" action="/admin/profile/api-tokens" class="flex gap-2 max-w-sm">
					{!! csrf_field() !!}
					<input type="text" name="name" class="flex-1 border rounded px-3 py-2 text-sm" placeholder="Token name (e.g. Mobile App)" required>
					<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded">
						Generate Token
					</button>
				</form>
			</section>
		</div>
	</div>
@endsection
