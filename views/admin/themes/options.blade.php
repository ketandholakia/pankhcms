@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Theme Options — {{ $slug }}</h1>
        <a href="/admin/themes" class="text-gray-600 hover:underline">Back to Themes</a>
    </div>

    @if(isset($_GET['status']) && $_GET['status'] === 'saved')
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">Options saved.</div>
    @endif
    @if(isset($_GET['error']))
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">{{ urldecode($_GET['error']) }}</div>
    @endif

    <form method="POST" action="/admin/themes/options" class="bg-white border rounded shadow p-6 space-y-4">
        {!! csrf_field() !!}
        @foreach($options as $key => $option)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1" for="opt-{{ $key }}">{{ $option['label'] }}</label>
                @if($option['type'] === 'color')
                    <input type="color" id="opt-{{ $key }}" name="{{ $key }}" value="{{ $option['value'] }}"
                           pattern="#[0-9a-fA-F]{6}" class="h-10 w-24 border rounded">
                    <span class="text-xs text-gray-500 ml-2">{{ $option['value'] }}</span>
                @else
                    <input type="text" id="opt-{{ $key }}" name="{{ $key }}" value="{{ $option['value'] }}"
                           class="w-full border-gray-300 rounded p-2 border">
                @endif
            </div>
        @endforeach

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Options</button>
    </form>
</div>
@endsection
