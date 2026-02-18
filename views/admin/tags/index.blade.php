@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Tags</h1>

    <div id="tag-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden">
        Saved successfully.
    </div>

    <button onclick="openAddModal()" class="mb-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Tag</button>

    <!-- Add/Edit Modal -->
    <div id="tag-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
            <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
            <h2 id="modal-title" class="text-xl font-bold mb-4">Add Tag</h2>
            <form id="tag-form">
                <input type="hidden" id="tag-id" name="id">
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="modal-name">Tag Name</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="modal-name" name="name" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="modal-slug">Slug</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="modal-slug" name="slug" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('modal-title').innerText = 'Add Tag';
        document.getElementById('tag-id').value = '';
        document.getElementById('modal-name').value = '';
        document.getElementById('modal-slug').value = '';
        document.getElementById('tag-modal').classList.remove('hidden');
    }
    function openEditModal(id, name, slug) {
        document.getElementById('modal-title').innerText = 'Edit Tag';
        document.getElementById('tag-id').value = id;
        document.getElementById('modal-name').value = name;
        document.getElementById('modal-slug').value = slug;
        document.getElementById('tag-modal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('tag-modal').classList.add('hidden');
    }
    function showNotice() {
        const notice = document.getElementById('tag-notice');
        notice.classList.remove('hidden');
        setTimeout(() => notice.classList.add('hidden'), 2000);
    }
    document.getElementById('tag-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('tag-id').value;
        const name = document.getElementById('modal-name').value;
        const slug = document.getElementById('modal-slug').value;
        const data = new FormData();
        data.append('name', name);
        data.append('slug', slug);
        let url = '/admin/tags';
        let method = 'POST';
        if (id) {
            url = `/admin/tags/${id}/update`;
        }
        const res = await fetch(url, { method, body: data });
        if (res.ok) {
            closeModal();
            showNotice();
            setTimeout(() => location.reload(), 500);
        }
    });
    async function deleteTag(id) {
        if (!confirm('Delete this tag?')) return;
        const res = await fetch(`/admin/tags/${id}/delete`, { method: 'POST' });
        if (res.ok) location.reload();
    }
    </script>

    @if(isset($tags) && count($tags))
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Name</th>
                    <th class="py-2 px-4 border-b">Slug</th>
                    <th class="py-2 px-4 border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr>
                        <td class="py-2 px-4 border-b">{{ $tag->id }}</td>
                        <td class="py-2 px-4 border-b">{{ $tag->name }}</td>
                        <td class="py-2 px-4 border-b">{{ $tag->slug }}</td>
                        <td class="py-2 px-4 border-b">
                            <button onclick="openEditModal('{{ $tag->id }}', '{{ addslashes($tag->name) }}', '{{ addslashes($tag->slug) }}')" class="bg-yellow-400 text-white px-2 py-1 rounded mr-2">Edit</button>
                            <button onclick="deleteTag('{{ $tag->id }}')" class="bg-red-600 text-white px-2 py-1 rounded">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No tags found.</p>
    @endif
</div>
@endsection
