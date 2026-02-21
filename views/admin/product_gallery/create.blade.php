@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Add Product Gallery Image</h1>
    <form action="/admin/product-gallery/store" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
        {!! csrf_field() !!}
        <div class="mb-3">
            <label class="block font-semibold mb-1">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Image</label>
            <input type="file" name="image" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Caption</label>
            <input type="text" name="caption" class="form-control">
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="0">
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Active</label>
            <input type="checkbox" name="active" value="1" checked>
        </div>
        <button type="submit" class="btn btn-success">Add Image</button>
    </form>
</div>
@endsection
