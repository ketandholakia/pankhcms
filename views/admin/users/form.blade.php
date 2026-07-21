@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->id ? 'Edit User' : 'Add User' }}</h1>
        <a href="/admin/users" class="text-gray-600 hover:underline">Back to Users</a>
    </div>

    @php $error = $_GET['error'] ?? null; @endphp
    @if($error)
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
            @if($error === 'username_required')
                Username is required.
            @elseif($error === 'username_taken')
                This username is already in use.
            @elseif($error === 'invalid_email')
                Please provide a valid email address.
            @elseif($error === 'email_taken')
                This email address is already in use.
            @elseif($error === 'password_required')
                Password is required for new users.
            @endif
        </div>
    @endif

    <form action="{{ $action }}" method="POST" class="bg-white border rounded shadow p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
            <input type="text" name="username" value="{{ $user->username }}" class="w-full border-gray-300 rounded p-2 border" required>
            <p class="mt-1 text-xs text-gray-500">This value can be used to sign in.</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Display Name</label>
            <input type="text" name="name" value="{{ $user->name }}" class="w-full border-gray-300 rounded p-2 border">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ $user->email }}" class="w-full border-gray-300 rounded p-2 border" required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Password {!! $user->id ? '<span class="text-gray-400 font-normal">(Leave blank to keep current)</span>' : '' !!}</label>
            <input type="password" name="password" class="w-full border-gray-300 rounded p-2 border" {{ $user->id ? '' : 'required' }}>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
            @php $currentRole = $user->roles->count() > 0 ? $user->roles[0]->id : null; @endphp
            <select name="role_id" class="w-full border-gray-300 rounded p-2 border">
                <option value="">-- No Role --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ $currentRole == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="pt-4">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save User</button>
        </div>
    </form>
</div>
@endsection
