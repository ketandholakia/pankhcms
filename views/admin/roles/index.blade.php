@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Manage Roles</h1>
        <a href="/admin/roles/create" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Role</a>
    </div>

    @if(isset($_GET['status']) && $_GET['status'] == 'deleted')
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Deleted!</strong>
        <span class="block sm:inline">The role has been removed.</span>
    </div>
    @endif

    <div class="bg-white border rounded shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="p-4 text-sm font-semibold text-gray-600">ID</th>
                    <th class="p-4 text-sm font-semibold text-gray-600">Name</th>
                    <th class="p-4 text-sm font-semibold text-gray-600">Permissions</th>
                    <th class="p-4 text-sm font-semibold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $r)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-4 text-sm text-gray-800">{{ $r->id }}</td>
                    <td class="p-4 text-sm font-semibold text-gray-800">{{ $r->name }}</td>
                    <td class="p-4 text-sm text-gray-500">
                        {{ $r->permissions->count() }} permissions
                    </td>
                    <td class="p-4 text-right space-x-2">
                        <a href="/admin/roles/edit/{{ $r->id }}" class="text-blue-600 hover:underline text-sm">Edit</a>
                        @if($r->name !== 'Administrator')
                        <form action="/admin/roles/delete/{{ $r->id }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
