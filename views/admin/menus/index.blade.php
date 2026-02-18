@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Menus</h1>

    <div id="menu-notice" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow hidden"></div>

    <!-- Create Menu -->
    <div class="mb-6 bg-white p-4 rounded shadow border border-gray-200">
        <h2 class="text-lg font-bold mb-2">Create Menu</h2>
        <form id="menu-create-form" class="grid grid-cols-1 md:grid-cols-2 gap-2">
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

    <!-- Menu Selector & Edit -->
    <form method="GET" class="mb-6 inline-block">
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
    <form action="/admin/menus/{{ $selectedMenu->id }}/delete" method="POST" class="inline js-menu-delete" onsubmit="return confirm('Delete this menu?');">
        <button type="submit" class="ml-2 bg-red-600 text-white px-3 py-1 rounded">Delete Menu</button>
    </form>
    @endif

    <!-- Edit Menu Modal -->
    <div id="menu-edit-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
            <button onclick="closeMenuEditModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
            <h2 class="text-xl font-bold mb-4">Edit Menu</h2>
            <form id="menu-edit-form">
                <input type="hidden" name="id" value="{{ $selectedMenu->id ?? '' }}">
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">Menu Name</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" name="name" value="{{ $selectedMenu->name ?? '' }}" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">Location</label>
                    <input class="w-full border px-3 py-2 rounded" type="text" name="location" value="{{ $selectedMenu->location ?? '' }}" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
            </form>
        </div>
    </div>

    @if(isset($selectedMenu) && $selectedMenu)
        <!-- Add Menu Item Form -->
        <div class="mb-6 bg-white p-4 rounded shadow border border-gray-200">
            <h2 class="text-lg font-bold mb-2">Add Menu Item</h2>
            <form id="add-menu-item-form">
                <input type="hidden" name="menu_id" value="{{ $selectedMenu->id }}">
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
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Menu Item</button>
            </form>
        </div>

        <!-- Menu Items List -->
        <div class="bg-white p-4 rounded shadow border border-gray-200">
            <h2 class="text-lg font-bold mb-2">Menu Items</h2>
            @php
                $tree = build_menu_tree($menuItems);
            @endphp
            @if(count($tree))
                <ul>
                    @foreach($tree as $item)
                        @include('admin.menus.menu_item', ['item' => $item, 'pages' => $pages, 'menuItems' => $menuItems])
                    @endforeach
                </ul>
            @else
                <p>No menu items found.</p>
            @endif
        </div>

        <!-- Edit Menu Item Modal -->
        <div id="menu-item-edit-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
            <div class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
                <button onclick="closeMenuItemEditModal()" class="absolute top-2 right-2 text-gray-500">&times;</button>
                <h2 class="text-xl font-bold mb-4">Edit Menu Item</h2>
                <form id="menu-item-edit-form">
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

        <script>
        function showNotice(message) {
            const notice = document.getElementById('menu-notice');
            notice.textContent = message;
            notice.classList.remove('hidden');
            setTimeout(() => notice.classList.add('hidden'), 2000);
        }

        @if(isset($_GET['notice']))
            showNotice(@json($_GET['notice']));
        @endif

        // Menu edit modal
        function openMenuEditModal() {
            document.getElementById('menu-edit-modal').classList.remove('hidden');
        }
        function closeMenuEditModal() {
            document.getElementById('menu-edit-modal').classList.add('hidden');
        }
        document.getElementById('menu-edit-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
            const res = await fetch('/admin/menus/' + data.get('id') + '/update', {
                method: 'POST',
                body: data
            });
            if (res.ok) {
                showNotice('Menu saved successfully.');
                setTimeout(() => location.reload(), 500);
            }
        });

        // Menu create
        document.getElementById('menu-create-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
            const res = await fetch('/admin/menus', {
                method: 'POST',
                body: data
            });
            if (res.ok) {
                showNotice('Menu created successfully.');
                setTimeout(() => location.reload(), 500);
            }
        });

        // Menu item add
        document.getElementById('add-menu-item-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
            const res = await fetch('/admin/menu-items', {
                method: 'POST',
                body: data
            });
            if (res.ok) {
                showNotice('Menu item added successfully.');
                setTimeout(() => location.reload(), 500);
            }
        });

        // Menu item edit modal
        function openMenuItemEditModal(item) {
            document.getElementById('edit-item-id').value = item.id;
            document.getElementById('edit-item-title').value = item.title;
            document.getElementById('edit-item-url').value = item.url || '';
            document.getElementById('edit-item-page_id').value = item.page_id || '';
            document.getElementById('edit-item-parent_id').value = item.parent_id || '';
            document.getElementById('edit-item-sort_order').value = item.sort_order || 0;
            document.getElementById('menu-item-edit-modal').classList.remove('hidden');
        }
        function closeMenuItemEditModal() {
            document.getElementById('menu-item-edit-modal').classList.add('hidden');
        }
        document.getElementById('menu-item-edit-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
            const res = await fetch('/admin/menu-items/' + data.get('id') + '/update', {
                method: 'POST',
                body: data
            });
            if (res.ok) {
                showNotice('Menu item saved successfully.');
                setTimeout(() => location.reload(), 500);
            }
        });

        document.querySelectorAll('.js-menu-delete, .js-menu-item-delete').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const res = await fetch(form.action, { method: 'POST' });
                if (res.ok) {
                    showNotice('Deleted successfully.');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
        </script>
    @endif

    <script>
    if (!window.showNotice) {
        window.showNotice = function(message) {
            const notice = document.getElementById('menu-notice');
            if (!notice) return;
            notice.textContent = message;
            notice.classList.remove('hidden');
            setTimeout(() => notice.classList.add('hidden'), 2000);
        };
    }

    const menuCreateForm = document.getElementById('menu-create-form');
    if (menuCreateForm && !menuCreateForm.dataset.bound) {
        menuCreateForm.dataset.bound = '1';
        menuCreateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = new FormData(menuCreateForm);
            const res = await fetch('/admin/menus', {
                method: 'POST',
                body: data
            });
            if (res.ok) {
                showNotice('Menu created successfully.');
                setTimeout(() => location.reload(), 500);
            }
        });
    }
    </script>
</div>
@endsection
