@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-4xl">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ $role->id ? 'Edit Role' : 'Add Role' }}</h1>
        <a href="/admin/roles" class="text-gray-600 hover:underline">Back to Roles</a>
    </div>

    <form action="{{ $action }}" method="POST" class="bg-white border rounded shadow p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Role Name</label>
            <input type="text" name="name" value="{{ $role->name }}" class="w-full border-gray-300 rounded p-2 border" {{ $role->name === 'Administrator' ? 'readonly' : 'required' }}>
            @if($role->name === 'Administrator')
                <p class="text-xs text-gray-500 mt-1">The Administrator role name cannot be changed.</p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-3">Permissions</label>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-gray-50 p-4 rounded border">
                @php 
                    $rolePerms = $role->permissions->pluck('id')->toArray(); 
                @endphp
                
                @foreach($permissions as $perm)
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="rounded text-blue-600 focus:ring-blue-500" {{ in_array($perm->id, $rolePerms) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-800">{{ $perm->name }}</span>
                </label>
                @endforeach
            </div>
            
            @if($role->name === 'Administrator')
                <p class="text-xs text-orange-600 mt-2 font-semibold"><i data-lucide="alert-circle" class="inline w-3 h-3"></i> Warning: Removing permissions from Administrator may lock you out.</p>
            @endif
        </div>

        <div class="pt-4 border-t">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Role</button>
        </div>
    </form>
</div>
@endsection
