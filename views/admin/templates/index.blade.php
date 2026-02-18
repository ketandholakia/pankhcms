@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1 class="text-2xl font-bold mb-4">Templates</h1>


        <!-- Add Template Button -->
        <button onclick="openAddModal()" class="mb-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Template</button>

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
                url = `/admin/templates/${id}/update`;
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

        @if(isset($templates) && count($templates))
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Name</th>
                        <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td class="py-2 px-4 border-b">{{ $template->id }}</td>
                            <td class="py-2 px-4 border-b">{{ $template->name }}</td>
                            <td class="py-2 px-4 border-b">
                                <button onclick="openEditModal({{ $template->id }}, {{ json_encode($template->name) }}, {{ json_encode($template->content_json) }})" class="bg-yellow-400 text-white px-2 py-1 rounded mr-2">Edit</button>
                                <button onclick="deleteTemplate('{{ $template->id }}')" class="bg-red-600 text-white px-2 py-1 rounded">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No templates found.</p>
        @endif
    </div>
@endsection
