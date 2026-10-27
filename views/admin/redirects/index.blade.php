@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Redirects</h1>
    </div>

    @if(isset($_GET['status']))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">Redirect saved.</div>
    @endif
    @if(isset($_GET['error']))
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
            @if($_GET['error'] === 'missing') Both the from-path and the target URL are required.
            @elseif($_GET['error'] === 'notfound') Redirect not found.
            @else {{ urldecode($_GET['error']) }}
            @endif
        </div>
    @endif

    <form method="POST" action="/admin/redirects/store" class="bg-white border rounded shadow p-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        {!! csrf_field() !!}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1" for="old_url">From path</label>
            <input type="text" id="old_url" name="old_url" placeholder="/old-page" class="w-full border-gray-300 rounded p-2 border" required>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1" for="new_url">To URL</label>
            <input type="text" id="new_url" name="new_url" placeholder="/new-page or https://…" class="w-full border-gray-300 rounded p-2 border" required>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1" for="type">Type</label>
            <select id="type" name="type" class="w-full border-gray-300 rounded p-2 border">
                <option value="301">301 — permanent</option>
                <option value="302">302 — temporary</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add redirect</button>
    </form>

    <div class="bg-white border rounded shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">From</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">To</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Type</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($redirects as $redirect)
                    <tr class="border-t">
                        <td class="px-4 py-2 text-sm">{{ $redirect->old_url }}</td>
                        <td class="px-4 py-2 text-sm">{{ $redirect->new_url }}</td>
                        <td class="px-4 py-2 text-sm">{{ $redirect->type }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="/admin/redirects/delete/{{ $redirect->id }}" class="inline" onsubmit="return confirm('Delete this redirect?')">
                                {!! csrf_field() !!}
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No redirects defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
