@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4">Add Block Placement</h1>
    <form action="/admin/block-placements" method="POST">
        @csrf
        <div class="mb-3">
            <label for="block_id" class="form-label">Block</label>
            <select class="form-control" id="block_id" name="block_id" required>
                @foreach($blocks as $block)
                    <option value="{{ $block->id }}">{{ $block->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="page_id" class="form-label">Page ID</label>
            <input type="number" class="form-control" id="page_id" name="page_id">
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location">
        </div>
        <div class="mb-3">
            <label for="sort_order" class="form-label">Order</label>
            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
        </div>
        <button type="submit" class="btn btn-success">Create Placement</button>
        <a href="/admin/block-placements" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
