@extends('layouts.admin')
@section('content')
<div class="container mx-auto p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Slider Images</h2>
        <a href="/admin/slider/create" class="bg-blue-600 text-white px-4 py-2 rounded">Add New</a>
    </div>
    <table class="min-w-full bg-white rounded shadow">
        <thead>
            <tr>
                <th class="px-4 py-2">Image</th>
                <th class="px-4 py-2">Caption</th>
                <th class="px-4 py-2">Link</th>
                <th class="px-4 py-2">Order</th>
                <th class="px-4 py-2">Active</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sliders as $slider)
            <tr>
                <td class="px-4 py-2"><img src="{{ $slider->image_path }}" alt="" class="h-16 rounded"></td>
                <td class="px-4 py-2">{{ $slider->caption }}</td>
                <td class="px-4 py-2">{{ $slider->link }}</td>
                <td class="px-4 py-2">{{ $slider->sort_order }}</td>
                <td class="px-4 py-2">{{ $slider->active ? 'Yes' : 'No' }}</td>
                <td class="px-4 py-2">
                    <a href="/admin/slider/edit/{{ $slider->id }}" class="text-blue-600">Edit</a>
                    <form action="/admin/slider/delete/{{ $slider->id }}" method="POST" style="display:inline;">
                        {!! csrf_field() !!}
                        <button type="submit" class="text-red-600 ml-2" onclick="return confirm('Delete this image?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
