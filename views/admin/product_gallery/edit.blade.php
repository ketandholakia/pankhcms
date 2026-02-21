@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Edit Product Gallery Image</h1>
    <form action="/admin/product-gallery/update/{{ $gallery->id }}" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
        {!! csrf_field() !!}
        <div class="mb-3">
            <label class="block font-semibold mb-1">Title</label>
            <input type="text" name="title" class="form-control" value="{{ $gallery->title }}" required>
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Image</label>
            <input type="file" name="image" class="form-control">
            @if($gallery->image_path)
                <img src="{{ $gallery->image_path }}" alt="Current Image" style="height:80px;width:auto;margin-top:10px;">
            @endif
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Caption</label>
            <input type="text" name="caption" class="form-control" value="{{ $gallery->caption }}">
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="{{ $gallery->sort_order }}">
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Active</label>
            <input type="checkbox" name="active" value="1" @if($gallery->active) checked @endif>
        </div>
        <button type="submit" class="btn btn-primary">Update Image</button>
    </form>
</div>
@endsection
