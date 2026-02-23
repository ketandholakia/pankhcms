@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
            <p class="text-sm text-gray-500">Manage and edit your categories</p>
        </div>
        <button onclick="openAddModal()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
            <i data-lucide="plus" class="h-5 w-5"></i>
            Add Category
        </button>
    </div>

    <!-- Only keep the second table rendering below -->
    <div id="category-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden">
        Saved successfully.
    </div>

    <!-- Add/Edit Modal -->
    <div id="category-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
            <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
            <h2 id="modal-title" class="text-xl font-bold mb-4">Add Category</h2>
            <form id="category-form">
                <input type="hidden" id="category-id" name="id">
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="modal-name">Category Name</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="modal-name" name="name" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="modal-slug">Slug</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="modal-slug" name="slug" required>
                </div>

                    <script>
                    // Slugify function
                    function slugify(text) {
                        return text.toString().toLowerCase()
                            .replace(/\s+/g, '-')           // Replace spaces with -
                            .replace(/[^a-z0-9\-]/g, '')   // Remove non-alphanum
                            .replace(/-+/g, '-')            // Collapse multiple -
                            .replace(/^-+|-+$/g, '');       // Trim -
                    }
                    let slugAuto = true;
                    document.getElementById('modal-name').addEventListener('input', function() {
                        if (slugAuto) {
                            document.getElementById('modal-slug').value = slugify(this.value);
                        }
                    });
                    document.getElementById('modal-slug').addEventListener('input', function() {
                        slugAuto = false;
                    });
                    document.getElementById('modal-name').addEventListener('focus', function() {
                        slugAuto = true;
                    });
                    </script>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="modal-parent">Parent Category</label>
                    <select class="w-full border px-3 py-2 rounded" id="modal-parent" name="parent_id">
                        <option value="">None</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('modal-title').innerText = 'Add Category';
        document.getElementById('category-id').value = '';
        document.getElementById('modal-name').value = '';
        document.getElementById('modal-slug').value = '';
        document.getElementById('modal-parent').value = '';
        document.getElementById('category-modal').classList.remove('hidden');
    }
    function openEditModal(id, name, slug, parent_id) {
        document.getElementById('modal-title').innerText = 'Edit Category';
        document.getElementById('category-id').value = id;
        document.getElementById('modal-name').value = name;
        document.getElementById('modal-slug').value = slug;
        document.getElementById('modal-parent').value = parent_id || '';
        document.getElementById('category-modal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('category-modal').classList.add('hidden');
    }
    function showNotice() {
        const notice = document.getElementById('category-notice');
        notice.classList.remove('hidden');
        setTimeout(() => notice.classList.add('hidden'), 2000);
    }
    document.getElementById('category-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('category-id').value;
        const name = document.getElementById('modal-name').value;
        const slug = document.getElementById('modal-slug').value;
        const parent_id = document.getElementById('modal-parent').value;
        const data = new FormData();
        data.append('name', name);
        data.append('slug', slug);
        if (parent_id) data.append('parent_id', parent_id);
        let url = '/admin/categories';
        let method = 'POST';
        if (id) {
            url = `/admin/categories/${id}/update`;
        }
        const res = await fetch(url, { method, body: data });
        if (res.ok) {
            closeModal();
            showNotice();
            setTimeout(() => location.reload(), 500);
        }
    });
    async function deleteCategory(id) {
        if (!confirm('Delete this category?')) return;
        const res = await fetch(`/admin/categories/${id}/delete`, { method: 'POST' });
        if (res.ok) location.reload();
    }
    </script>

    @if(isset($categories) && count($categories))
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Name</th>
                    <th class="py-2 px-4 border-b">Slug</th>
                    <th class="py-2 px-4 border-b">Parent</th>
                    <th class="py-2 px-4 border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                    <tr>
                        <td class="py-2 px-4 border-b">{{ $cat->id }}</td>
                        <td class="py-2 px-4 border-b">{{ $cat->name }}</td>
                        <td class="py-2 px-4 border-b">{{ $cat->slug }}</td>
                        <td class="py-2 px-4 border-b">
                            @php
                                $parent = $categories->firstWhere('id', $cat->parent_id);
                            @endphp
                            {{ $parent ? $parent->name : '' }}
                        </td>
                        <td class="py-2 px-4 border-b">
                            <button onclick="openEditModal('{{ $cat->id }}', '{{ addslashes($cat->name) }}', '{{ addslashes($cat->slug) }}', '{{ $cat->parent_id }}')" class="bg-yellow-400 text-white px-2 py-1 rounded mr-2">Edit</button>
                            <button onclick="deleteCategory('{{ $cat->id }}')" class="bg-red-600 text-white px-2 py-1 rounded">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No categories found.</p>
    @endif
</div>
@endsection
