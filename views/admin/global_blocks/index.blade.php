@extends('layouts.admin')

@section('content')
        <div class="w-full p-8 bg-gray-50 min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Global Blocks</h1>
                    <p class="text-sm text-gray-500">Create and manage reusable blocks used across the site</p>
                </div>
                <button onclick="openAddModal()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    Add Block
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if(isset($blocks) && count($blocks))
                            @foreach($blocks as $block)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-400">#{{ $block->id }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $block->title }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $block->type }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $block->location }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $block->status ? 'Active' : 'Inactive' }}</td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick="openEditModal({{ $block->id }}, {{ json_encode($block->title) }}, {{ json_encode($block->type) }}, {{ json_encode($block->location) }}, {{ json_encode($block->content) }}, {{ $block->status ? '1' : '0' }})" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-medium text-sm">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button onclick="deleteBlock('{{ $block->id }}')" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No global blocks found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    <div id="gb-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden">Saved successfully.</div>

    <!-- Add/Edit Modal -->
    <div id="gb-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
            <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
            <h2 id="gb-modal-title" class="text-xl font-bold mb-4">Add Global Block</h2>
            <form id="gb-form">
                <input type="hidden" id="gb-id" name="id">
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Title</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="gb-title" name="title" required>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Type</label>
                    <select id="gb-type" name="type" class="w-full border px-3 py-2 rounded">
                        <option value="text">Text</option>
                        <option value="contact_info">Contact Info</option>
                        <option value="cta">Call to Action</option>
                        <option value="social_links">Social Links</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Location</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="gb-location" name="location">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Content</label>
                    <textarea id="gb-content" name="content" rows="4" class="w-full border px-3 py-2 rounded"></textarea>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Status</label>
                    <select id="gb-status" name="status" class="w-full border px-3 py-2 rounded">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('gb-modal-title').innerText = 'Add Global Block';
            document.getElementById('gb-id').value = '';
            document.getElementById('gb-title').value = '';
            document.getElementById('gb-type').value = 'text';
            document.getElementById('gb-location').value = '';
            document.getElementById('gb-content').value = '';
            document.getElementById('gb-status').value = '1';
            document.getElementById('gb-modal').classList.remove('hidden');
        }
        function openEditModal(id, title, type, location, content, status) {
            document.getElementById('gb-modal-title').innerText = 'Edit Global Block';
            document.getElementById('gb-id').value = id;
            document.getElementById('gb-title').value = title;
            document.getElementById('gb-type').value = type;
            document.getElementById('gb-location').value = location;
            document.getElementById('gb-content').value = content;
            document.getElementById('gb-status').value = status ? '1' : '0';
            document.getElementById('gb-modal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('gb-modal').classList.add('hidden');
        }
        function showNotice() {
            const n = document.getElementById('gb-notice');
            n.classList.remove('hidden');
            setTimeout(() => n.classList.add('hidden'), 1800);
        }

        document.getElementById('gb-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('gb-id').value;
            const data = new FormData();
            data.append('title', document.getElementById('gb-title').value);
            data.append('type', document.getElementById('gb-type').value);
            data.append('location', document.getElementById('gb-location').value);
            data.append('content', document.getElementById('gb-content').value);
            data.append('status', document.getElementById('gb-status').value);
            let url = '/admin/global-blocks';
            if (id) url = `/admin/global-blocks/${id}`;
            const res = await fetch(url, { method: 'POST', body: data });
            if (res.ok) {
                closeModal(); showNotice(); setTimeout(() => location.reload(), 500);
            }
        });

        async function deleteBlock(id) {
            if (!confirm('Delete this block?')) return;
            const res = await fetch(`/admin/global-blocks/${id}/delete`, { method: 'POST' });
            if (res.ok) location.reload();
        }
    </script>
@endsection
