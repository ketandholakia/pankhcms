@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Global Block</h1>
    <form action="/admin/global-blocks/{{ $block->id }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ $block->title }}" required>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-control" id="type" name="type" required>
                <option value="text" {{ $block->type == 'text' ? 'selected' : '' }}>Text</option>
                <option value="contact_info" {{ $block->type == 'contact_info' ? 'selected' : '' }}>Contact Info</option>
                <option value="cta" {{ $block->type == 'cta' ? 'selected' : '' }}>Call to Action</option>
                <option value="social_links" {{ $block->type == 'social_links' ? 'selected' : '' }}>Social Links</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" value="{{ $block->location }}" required>
        </div>
        <div class="mb-3">
            <label for="content" class="form-label">Content</label>
            <textarea class="form-control" id="content" name="content" rows="5">{{ $block->content }}</textarea>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="1" {{ $block->status ? 'selected' : '' }}>Active</option>
                <option value="0" {{ !$block->status ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="show_title" name="show_title" value="1" {{ $block->show_title ? 'checked' : '' }}>
            <label class="form-check-label" for="show_title">Show title</label>
        </div>
        <button type="submit" class="btn btn-success">Update Block</button>
        <a href="/admin/global-blocks" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
