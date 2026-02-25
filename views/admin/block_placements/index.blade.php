@extends('layouts.admin')

@section('content')
        <div class="w-full p-8 bg-gray-50 min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Block Placements</h1>
                    <p class="text-sm text-gray-500">Place global blocks on pages and sections</p>
                </div>
                <button onclick="openBPAddModal()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    Add Placement
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Block</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Page</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if(isset($placements) && count($placements))
                            @foreach($placements as $placement)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-400">#{{ $placement->id }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $placement->block->title ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $placement->page_id ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $placement->location ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $placement->sort_order }}</td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick="openBPEditModal({{ $placement->id }}, {{ $placement->block_id }}, {{ $placement->page_id ?? 'null' }}, {{ json_encode($placement->location) }}, {{ $placement->sort_order }})" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-medium text-sm">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button onclick="deletePlacement('{{ $placement->id }}')" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No block placements found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    <div id="bp-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden">Saved successfully.</div>

    <!-- Add/Edit Modal -->
    <div id="bp-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
            <button onclick="closeBPModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
            <h2 id="bp-modal-title" class="text-xl font-bold mb-4">Add Block Placement</h2>
            <form id="bp-form">
                <input type="hidden" id="bp-id" name="id">
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Block</label>
                    <select id="bp-block_id" name="block_id" class="w-full border px-3 py-2 rounded">
                        @foreach($blocks as $b)
                            <option value="{{ $b->id }}">{{ $b->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Page ID</label>
                    <input class="w-full border px-3 py-2 rounded" type="number" id="bp-page_id" name="page_id">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Section</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" id="bp-location" name="location">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 font-semibold">Order</label>
                    <input class="w-full border px-3 py-2 rounded" type="number" id="bp-sort_order" name="sort_order" value="0">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
            </form>
        </div>
    </div>

    <script>
        function openBPAddModal() {
            document.getElementById('bp-modal-title').innerText = 'Add Block Placement';
            document.getElementById('bp-id').value = '';
            document.getElementById('bp-block_id').value = document.getElementById('bp-block_id').options[0].value;
            document.getElementById('bp-page_id').value = '';
            document.getElementById('bp-location').value = '';
            document.getElementById('bp-sort_order').value = '0';
            document.getElementById('bp-modal').classList.remove('hidden');
        }
        function openBPEditModal(id, block_id, page_id, location, order) {
            document.getElementById('bp-modal-title').innerText = 'Edit Block Placement';
            document.getElementById('bp-id').value = id;
            document.getElementById('bp-block_id').value = block_id;
            document.getElementById('bp-page_id').value = page_id || '';
            document.getElementById('bp-location').value = location || '';
            document.getElementById('bp-sort_order').value = order || 0;
            document.getElementById('bp-modal').classList.remove('hidden');
        }
        function closeBPModal() { document.getElementById('bp-modal').classList.add('hidden'); }
        function showBPNotice() { const n=document.getElementById('bp-notice'); n.classList.remove('hidden'); setTimeout(()=>n.classList.add('hidden'),1800); }

        document.getElementById('bp-form').addEventListener('submit', async function(e){
            e.preventDefault();
            const id = document.getElementById('bp-id').value;
            const data = new FormData();
            data.append('block_id', document.getElementById('bp-block_id').value);
            const pageVal = document.getElementById('bp-page_id').value;
            if (pageVal !== '') data.append('page_id', pageVal);
            data.append('location', document.getElementById('bp-location').value || '');
            data.append('sort_order', document.getElementById('bp-sort_order').value || 0);
            let url = '/admin/block-placements';
            if (id) url = `/admin/block-placements/${id}`;
            const res = await fetch(url, { method: 'POST', body: data });
            if (res.ok) { closeBPModal(); showBPNotice(); setTimeout(()=>location.reload(),500); }
            else {
                const txt = await res.text().catch(()=>'(no response body)');
                alert('Save failed: ' + res.status + '\n' + txt);
                console.error('Save failed', res.status, txt);
            }
        });

        async function deletePlacement(id){ if(!confirm('Delete this placement?')) return; const res=await fetch(`/admin/block-placements/${id}/delete`,{method:'POST'}); if(res.ok) location.reload(); }
    </script>
@endsection
