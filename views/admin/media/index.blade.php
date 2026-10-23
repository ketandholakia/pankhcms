@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="image"></i>
                Media
            </h1>
            <p class="text-sm text-gray-500">Manage uploaded images and files</p>
        </div>
    </div>

    <div id="media-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden">
        Saved successfully.
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="/admin/media" class="flex flex-wrap items-end gap-4 mb-6">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 mb-1" for="media-search">Search</label>
                <input type="text" id="media-search" name="search" value="{{ $search }}" placeholder="Name, title, alt or description" class="w-full border-gray-300 rounded p-2 border">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1" for="media-type">Type</label>
                <select id="media-type" name="type" class="border-gray-300 rounded p-2 border">
                    <option value="">All types</option>
                    <option value="image/" {{ str_starts_with((string) $type, 'image/') ? 'selected' : '' }}>Images</option>
                    <option value="application/pdf" {{ $type === 'application/pdf' ? 'selected' : '' }}>PDF</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1" for="media-date">Date</label>
                <input type="date" id="media-date" name="date" value="{{ $date }}" class="border-gray-300 rounded p-2 border">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
            <a href="/admin/media" class="px-4 py-2 rounded border text-gray-600 hover:bg-gray-100">Reset</a>
        </form>

        <form id="media-upload-form" class="flex items-center gap-4 border-t pt-6">
            <input type="file" id="media-file-input" name="file" required class="border-gray-300 rounded p-2 border flex-1">
            <button type="submit" id="media-upload-btn" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Upload</button>
        </form>
    </div>

    @if($media->isEmpty())
        <div class="bg-white border rounded shadow p-8 text-center text-gray-500">
            No media found.
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($media as $item)
                <div class="bg-white border rounded shadow overflow-hidden flex flex-col">
                    @if(str_starts_with((string) $item->mime_type, 'image/'))
                        <img src="{{ $item->url }}" alt="{{ $item->alt ?? $item->original_name }}" class="w-full" style="height:120px;object-fit:cover">
                    @else
                        <div class="flex items-center justify-center text-gray-500" style="height:120px">
                            <i data-lucide="file" class="w-8 h-8"></i>
                        </div>
                    @endif
                    <div class="p-2 text-center flex-1 flex flex-col justify-between gap-2">
                        <div>
                            <p class="text-xs text-gray-700 truncate" title="{{ $item->original_name }}">{{ $item->original_name ?? $item->filename }}</p>
                            <p class="text-xs text-gray-400">{{ $item->mime_type }}</p>
                        </div>
                        <form method="POST" action="/admin/media/{{ $item->id }}/delete" onsubmit="return confirm('Delete this file?')">
                            {!! csrf_field() !!}
                            <button type="submit" class="w-full text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
document.getElementById('media-upload-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const input = document.getElementById('media-file-input');
    if (!input.files.length) {
        return;
    }
    const btn = document.getElementById('media-upload-btn');
    btn.disabled = true;
    btn.textContent = 'Uploading...';

    const data = new FormData();
    data.append('file', input.files[0]);

    fetch('/admin/media/upload', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: data
    }).then(function (res) { return res.json(); }).then(function () {
        window.location.reload();
    }).catch(function () {
        btn.disabled = false;
        btn.textContent = 'Upload';
        alert('Upload failed.');
    });
});
</script>
@endsection
