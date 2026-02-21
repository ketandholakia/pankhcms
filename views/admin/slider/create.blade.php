@extends('layouts.admin')
@section('content')
<div class="container mx-auto p-6 max-w-lg">
    <h2 class="text-2xl font-bold mb-4">Add Slider Image</h2>
    <form action="/admin/slider/store" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
        {!! csrf_field() !!}
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Image</label>
            <input type="file" name="image" required class="border rounded w-full p-2">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Caption</label>
            <input type="text" name="caption" class="border rounded w-full p-2">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Link (optional)</label>
            <input type="text" name="link" class="border rounded w-full p-2">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Sort Order</label>
            <input type="number" name="sort_order" value="0" class="border rounded w-full p-2">
        </div>
        <div class="mb-4">
            <label class="inline-flex items-center">
                <input type="checkbox" name="active" value="1" checked class="mr-2">
                Active
            </label>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
        <a href="/admin/slider" class="ml-4 text-gray-600">Cancel</a>
    </form>
</div>
@endsection
