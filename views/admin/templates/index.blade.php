@extends('layouts.admin')

@section('content')
        <div class="w-full p-8 bg-gray-50 min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Templates</h1>
                    <p class="text-sm text-gray-500">Manage and edit your layout blocks</p>
                </div>
                <button onclick="openAddModal()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add Template
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Template Name</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if(isset($templates) && count($templates))
                            @foreach($templates as $template)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-400">#{{ $template->id }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $template->name }}</td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick="openEditModal({{ $template->id }}, {{ json_encode($template->name) }}, {{ json_encode($template->content_json) }})" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-medium text-sm">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button onclick="deleteTemplate('{{ $template->id }}')" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">No templates found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div id="template-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden">
                Saved successfully.
            </div>

            <!-- Add/Edit Modal -->
            <div id="template-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
                    <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
                    <h2 id="modal-title" class="text-xl font-bold mb-4">Add Template</h2>
                    <form id="template-form">
                        <input type="hidden" id="template-id" name="id">
                        <div class="mb-4">
                            <label class="block mb-1 font-semibold" for="modal-name">Template Name</label>
                            <input class="w-full border px-3 py-2 rounded" type="text" id="modal-name" name="name" required>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-semibold" for="modal-content_json">Content (JSON)</label>
                            <textarea class="w-full border px-3 py-2 rounded" id="modal-content_json" name="content_json" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                    </form>
                </div>
            </div>

            <script>
                function openAddModal() {
                    document.getElementById('modal-title').innerText = 'Add Template';
                    document.getElementById('template-id').value = '';
                    document.getElementById('modal-name').value = '';
                    document.getElementById('modal-content_json').value = '';
                    document.getElementById('template-modal').classList.remove('hidden');
                }
                function openEditModal(id, name, content_json) {
                    document.getElementById('modal-title').innerText = 'Edit Template';
                    document.getElementById('template-id').value = id;
                    document.getElementById('modal-name').value = name;
                    document.getElementById('modal-content_json').value = content_json;
                    document.getElementById('template-modal').classList.remove('hidden');
                }
                function closeModal() {
                    document.getElementById('template-modal').classList.add('hidden');
                }
                function showNotice() {
                    const notice = document.getElementById('template-notice');
                    notice.classList.remove('hidden');
                    setTimeout(() => notice.classList.add('hidden'), 2000);
                }
                document.getElementById('template-form').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const id = document.getElementById('template-id').value;
                    const name = document.getElementById('modal-name').value;
                    const content_json = document.getElementById('modal-content_json').value;
                    const data = new FormData();
                    data.append('name', name);
                    data.append('content_json', content_json);
                    let url = '/admin/templates';
                    let method = 'POST';
                    if (id) {
                        url = `/admin/templates/${id}`;
                    }
                    const res = await fetch(url, { method, body: data });
                    if (res.ok) {
                        closeModal();
                        showNotice();
                        setTimeout(() => location.reload(), 500);
                    }
                });
                async function deleteTemplate(id) {
                    if (!confirm('Delete this template?')) return;
                    const res = await fetch(`/admin/templates/${id}/delete`, { method: 'POST' });
                    if (res.ok) location.reload();
                }
            </script>
        </div>
@endsection
