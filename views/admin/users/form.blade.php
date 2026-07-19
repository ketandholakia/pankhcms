@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->id ? 'Edit User' : 'Add User' }}</h1>
        <a href="/admin/users" class="text-gray-600 hover:underline">Back to Users</a>
    </div>

    <form action="{{ $action }}" method="POST" class="bg-white border rounded shadow p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
            <input type="text" name="name" value="{{ $user->name }}" class="w-full border-gray-300 rounded p-2 border" required>
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
