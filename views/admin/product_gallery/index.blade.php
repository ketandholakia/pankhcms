@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Product Gallery</h1>
    <a href="/admin/product-gallery/create" class="btn btn-primary mb-4">Add New Product Image</a>
    <table class="table-auto w-full bg-white rounded shadow">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Caption</th>
                <th>Sort Order</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($galleries as $gallery)
            <tr>
                <td><img src="{{ $gallery->image_path }}" alt="{{ $gallery->title }}" style="height:60px;width:auto;"></td>
                <td>{{ $gallery->title }}</td>
                <td>{{ $gallery->caption }}</td>
                <td>{{ $gallery->sort_order }}</td>
                <td>{{ $gallery->active ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="/admin/product-gallery/edit/{{ $gallery->id }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="/admin/product-gallery/delete/{{ $gallery->id }}" method="POST" style="display:inline;">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this image?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
