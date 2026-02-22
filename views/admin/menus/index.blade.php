@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Menus</h1>

    <div id="menu-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden"></div>

    <!-- Create Menu -->
    <div class="mb-6 bg-white p-4 rounded shadow border border-gray-200">
        <h2 class="text-lg font-bold mb-2">Create Menu</h2>
        <form id="menu-create-form" class="grid grid-cols-1 md:grid-cols-2 gap-2" data-endpoint="/admin/menus" action="/admin/menus" method="POST">
            <div>
                <label class="block mb-1 font-semibold">Menu Name</label>
                <input class="w-full border px-3 py-2 rounded" type="text" name="name" required>
            </div>
            <div>
                <label class="block mb-1 font-semibold">Location</label>
                <input class="w-full border px-3 py-2 rounded" type="text" name="location" required>
            </div>
            <div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create</button>
            </div>
        </form>
    </div>

    <!-- Menu Selector & Edit/Delete -->
    <div class="mb-6">
        <form method="GET" class="inline-block">
            <label class="font-semibold mr-2">Select Menu:</label>
            <select name="menu_id" class="border px-2 py-1 rounded" onchange="this.form.submit()">
                <option value="">-- Choose --</option>
                @foreach($menus as $menu)
                    <option value="{{ $menu->id }}" @if(isset($selectedMenu) && $selectedMenu && $selectedMenu->id == $menu->id) selected @endif>{{ $menu->name }}</option>
                @endforeach
            </select>
        </form>
        @if(isset($selectedMenu) && $selectedMenu)
            <button onclick="openMenuEditModal()" class="ml-4 bg-yellow-400 text-white px-3 py-1 rounded">Edit Menu</button>
            <form action="/admin/menus/{{ $selectedMenu->id }}/delete" method="POST" class="inline js-menu-delete">
                <button type="submit" class="ml-2 bg-red-600 text-white px-3 py-1 rounded">Delete Menu</button>
            </form>
        @endif
    </div>

    @if(isset($selectedMenu) && $selectedMenu)
        <!-- Add Menu Item Form -->
        <div class="mb-6 bg-white p-4 rounded shadow border border-gray-200">
            <h2 class="text-lg font-bold mb-2">Add Menu Item</h2>
            <form id="add-menu-item-form" data-endpoint="/admin/menu-items" action="/admin/menu-items" method="POST">
                <input type="hidden" name="menu_id" value="{{ $selectedMenu->id }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Title</label>
                        <input class="w-full border px-3 py-2 rounded" type="text" name="title" required>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Page</label>
                        <select class="w-full border px-3 py-2 rounded" name="page_id">
                            <option value="">Custom URL</option>
                            @foreach($pages as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Custom URL</label>
                        <input class="w-full border px-3 py-2 rounded" type="text" name="url" placeholder="/custom-link">
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Parent</label>
                        <select class="w-full border px-3 py-2 rounded" name="parent_id">
                            <option value="">None</option>
                            @foreach($menuItems as $item)
                                <option value="{{ $item->id }}">{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Menu Item</button>
            </form>
        </div>

        <!-- Menu Items List -->
        <div class="bg-white p-4 rounded shadow border border-gray-200">
            <h2 class="text-lg font-bold mb-2">Menu Items</h2>
            @if(count($menuItems) > 0)
                <ul>
                    @foreach($menuItems as $item)
                        @include('admin.menus.menu_item', ['item' => $item, 'pages' => $pages, 'menuItems' => $menuItems])
                    @endforeach
                </ul>
            @else
                <p>No menu items found.</p>
            @endif
        </div>
    @endif

    <!-- Modals (Outside conditional for safety) -->
    @if(isset($selectedMenu) && $selectedMenu)
        <!-- Edit Menu Modal -->
        <div id="menu-edit-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
            <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
                <button onclick="closeMenuEditModal()" class="absolute top-2 right-2 text-gray-500 text-2xl" type="button">&times;</button>
                <h2 class="text-xl font-bold mb-4">Edit Menu</h2>
                <form id="menu-edit-form" data-endpoint="/admin/menus/{{ $selectedMenu->id }}/update" action="/admin/menus/{{ $selectedMenu->id }}/update" method="POST">
                    <input type="hidden" name="id" value="{{ $selectedMenu->id }}">
                    <div class="mb-4">
                        <label class="block mb-1 font-semibold">Menu Name</label>
                        <input class="w-full border px-3 py-2 rounded" type="text" name="name" value="{{ $selectedMenu->name }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-1 font-semibold">Location</label>
                        <input class="w-full border px-3 py-2 rounded" type="text" name="location" value="{{ $selectedMenu->location }}" required>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                </form>
            </div>
        </div>

        <!-- Edit Menu Item Modal -->
        <div id="menu-item-edit-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
            <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
                <button onclick="closeMenuItemEditModal()" class="absolute top-2 right-2 text-gray-500 text-2xl" type="button">&times;</button>
                <h2 class="text-xl font-bold mb-4">Edit Menu Item</h2>
                <form id="menu-item-edit-form" method="POST" action="#" data-endpoint="/admin/menu-items/{{ isset($item) ? $item->id : '' }}/update">
                    <input type="hidden" name="id" id="edit-item-id">
                    <input type="hidden" name="menu_id" value="{{ $selectedMenu->id }}">
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Title</label>
                        <input class="w-full border px-3 py-2 rounded" type="text" name="title" id="edit-item-title" required>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Page</label>
                        <select class="w-full border px-3 py-2 rounded" name="page_id" id="edit-item-page_id">
                            <option value="">Custom URL</option>
                            @foreach($pages as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Custom URL</label>
                        <input class="w-full border px-3 py-2 rounded" type="text" name="url" id="edit-item-url" placeholder="/custom-link">
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Parent</label>
                        <select class="w-full border px-3 py-2 rounded" name="parent_id" id="edit-item-parent_id">
                            <option value="">None</option>
                            @foreach($menuItems as $item)
                                <option value="{{ $item->id }}">{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1 font-semibold">Sort Order</label>
                        <input class="w-full border px-3 py-2 rounded" type="number" name="sort_order" id="edit-item-sort_order" min="0">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                </form>
            </div>
        </div>
    @endif

    <script>
    function showNotice(msg) {
        var n = document.getElementById('menu-notice');
        if (n) {
            n.innerText = msg;
            n.classList.remove('hidden');
            setTimeout(function() { n.classList.add('hidden'); }, 2000);
        }
    }

    // Modal control
    window.openMenuEditModal = function() {
        var el = document.getElementById('menu-edit-modal');
        if (el) el.classList.remove('hidden');
    }
    window.closeMenuEditModal = function() {
        var el = document.getElementById('menu-edit-modal');
        if (el) el.classList.add('hidden');
    }
    window.openMenuItemEditModal = function(item) {
        var ids = ['id', 'title', 'url', 'page_id', 'parent_id', 'sort_order'];
        for (var i = 0; i < ids.length; i++) {
            var key = ids[i];
            var el = document.getElementById('edit-item-' + key);
            if (el) {
                var val = item[key];
                if (!val) {
                    if (key === 'sort_order') val = 0;
                    else val = '';
                }
                    if (key === 'parent_id') {
                        // Set the select value for parent_id
                        el.value = val;
                    } else {
                        el.value = val;
                    }
            }
        }
        var modal = document.getElementById('menu-item-edit-modal');
        // Set the form endpoint and action dynamically for the edit modal
        try {
            var editForm = document.getElementById('menu-item-edit-form');
            if (editForm && item.id) {
                var endpoint = '/admin/menu-items/' + item.id + '/update';
                editForm.dataset.endpoint = endpoint;
                editForm.action = endpoint;
            }
        } catch (e) {}
        if (modal) modal.classList.remove('hidden');
    }
    window.closeMenuItemEditModal = function() {
        var el = document.getElementById('menu-item-edit-modal');
        if (el) el.classList.add('hidden');
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-menu-item-edit-btn');
        if (btn) {
            e.preventDefault();
            var item = {};
            item.id = btn.dataset.id;
            item.title = btn.dataset.title;
            item.url = btn.dataset.url;
                item.page_id = btn.dataset.pageId || '';
                item.parent_id = btn.dataset.parentId || '';
                item.sort_order = btn.dataset.sortOrder || '';
            window.openMenuItemEditModal(item);
        }
    });

    document.addEventListener('submit', function(e) {
        var form = e.target;
        var isForm = (form.id === 'menu-create-form' || form.id === 'menu-edit-form' || form.id === 'add-menu-item-form' || form.id === 'menu-item-edit-form');
        // Intercept menu item move (up/down) forms
        var isMoveForm = form.action && /\/admin\/menu-items\/.+\/move$/.test(form.action);
        if (isForm) {
            e.preventDefault();
            var data = new FormData(form);
            var url = '';
            // Prefer explicit form.action, then data-endpoint, then fallbacks
            if (form.action && form.action !== '#' && form.action !== '') {
                url = form.action;
            } else if (form.dataset && form.dataset.endpoint) {
                url = form.dataset.endpoint;
            } else {
                if (form.id === 'menu-create-form') url = '/admin/menus';
                else if (form.id === 'menu-edit-form') url = '/admin/menus/' + data.get('id') + '/update';
                else if (form.id === 'add-menu-item-form') url = '/admin/menu-items';
                else if (form.id === 'menu-item-edit-form') url = '/admin/menu-items/' + data.get('id') + '/update';
            }
            fetch(url, { method: 'POST', body: data }).then(function(res) {
                if (res.ok) {
                    showNotice('Saved successfully.');
                    setTimeout(function() { location.reload(); }, 500);
                }
            });
        } else if (isMoveForm) {
            e.preventDefault();
            var data = new FormData(form);
            fetch(form.action, { method: 'POST', body: data })
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    if (json && json.success) {
                        showNotice('Menu order updated.');
                        setTimeout(function() { location.reload(); }, 500);
                    } else {
                        showNotice(json && json.error ? json.error : 'Failed to update order.');
                    }
                })
                .catch(function() {
                    showNotice('Failed to update order.');
                });
        } else if (form.classList.contains('js-menu-delete') || form.classList.contains('js-menu-item-delete')) {
            e.preventDefault();
            if (!confirm('Are you sure?')) return;
            fetch(form.action, { method: 'POST' }).then(function(res) {
                if (res.ok) {
                    showNotice('Deleted.');
                    setTimeout(function() { location.reload(); }, 500);
                }
            });
        }
    });
    // Auto-fill URL when a page is selected (Add Menu Item form)
    document.querySelector('select[name="page_id"]').addEventListener('change', function(e) {
        var pageId = this.value;
        var urlInput = this.closest('form').querySelector('input[name="url"]');
        if (pageId && window.pageSlugs && window.pageSlugs[pageId]) {
            urlInput.value = '/' + window.pageSlugs[pageId];
            urlInput.readOnly = true;
        } else {
            urlInput.value = '';
            urlInput.readOnly = false;
        }
    });

    // Auto-fill URL when a page is selected (Edit Menu Item modal)
    var editPageSelect = document.getElementById('edit-item-page_id');
    if (editPageSelect) {
        editPageSelect.addEventListener('change', function(e) {
            var pageId = this.value;
            var urlInput = document.getElementById('edit-item-url');
            if (pageId && window.pageSlugs && window.pageSlugs[pageId]) {
                urlInput.value = '/' + window.pageSlugs[pageId];
                urlInput.readOnly = true;
            } else {
                urlInput.value = '';
                urlInput.readOnly = false;
            }
        });
    }

    // Provide page slugs to JS
    window.pageSlugs = {
        @foreach($pages as $p)
            {{ $p->id }}: '{{ addslashes($p->slug) }}',
        @endforeach
    };
    </script>
</div>
@endsection
