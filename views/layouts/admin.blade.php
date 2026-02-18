<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="flex">

    <aside id="admin-sidebar" class="w-64 bg-gray-900 text-gray-200 p-4 min-h-screen transition-all duration-200">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="box"></i>
                <span class="sidebar-label">CMS</span>
            </h2>

            <button id="sidebar-toggle" type="button" class="p-2 rounded hover:bg-gray-800" aria-label="Toggle sidebar">
                <i data-lucide="panel-left-close"></i>
            </button>
        </div>

        <nav class="space-y-1">
            <a href="/admin" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="layout-dashboard"></i>
                <span class="sidebar-label">Dashboard</span>
            </a>

            <a href="/admin/pages" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="file-text"></i>
                <span class="sidebar-label">Pages</span>
            </a>

            <a href="/admin/messages" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="inbox"></i>
                <span class="sidebar-label">Messages</span>
            </a>

            <a href="/admin/categories" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="folder"></i>
                <span class="sidebar-label">Categories</span>
            </a>

            <a href="/admin/tags" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="tag"></i>
                <span class="sidebar-label">Tags</span>
            </a>

            <a href="/admin/templates" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="layout"></i>
                <span class="sidebar-label">Templates</span>
            </a>

            <a href="/admin/themes" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="palette"></i>
                <span class="sidebar-label">Themes</span>
            </a>

            <a href="/admin/backups" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="database-backup"></i>
                <span class="sidebar-label">Backups</span>
            </a>

            <a href="/admin/settings/seo" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="search"></i>
                <span class="sidebar-label">SEO Settings</span>
            </a>

            <a href="/admin/settings/breadcrumbs" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="chevron-right-square"></i>
                <span class="sidebar-label">Breadcrumbs</span>
            </a>

            <a href="/admin/menus" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800">
                <i data-lucide="menu"></i>
                <span class="sidebar-label">Menus</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-6">
        @php
            $loggedInUser = \App\Core\Auth::user();
            $displayName = $loggedInUser->name ?? $loggedInUser->email ?? 'Admin';
        @endphp

        <div class="bg-white border rounded-lg px-4 py-3 mb-6 flex items-center justify-between">
            <a href="/" target="_blank" rel="noopener" class="flex items-center gap-2 text-blue-600 font-medium hover:underline">
                <i data-lucide="external-link"></i>
                View Website
            </a>

            <div class="flex items-center gap-4">
                <span class="flex items-center gap-2 text-sm text-gray-700">
                    <i data-lucide="user"></i>
                    {{ $displayName }}
                </span>
                <form method="POST" action="/admin/logout">
                    <button type="submit" class="flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold px-3 py-2 rounded">
                        <i data-lucide="log-out"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        @yield('content')
    </main>

</div>

@stack('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    const sidebar = document.getElementById('admin-sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarLabels = document.querySelectorAll('.sidebar-label');
    const sidebarLinks = document.querySelectorAll('.sidebar-link');

    function setSidebarCollapsed(collapsed) {
        if (!sidebar) {
            return;
        }

        sidebar.classList.toggle('w-64', !collapsed);
        sidebar.classList.toggle('w-20', collapsed);

        sidebarLabels.forEach((label) => {
            label.classList.toggle('hidden', collapsed);
        });

        sidebarLinks.forEach((link) => {
            link.classList.toggle('justify-center', collapsed);
            link.classList.toggle('px-2', collapsed);
            link.classList.toggle('px-3', !collapsed);
        });
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setSidebarCollapsed(!sidebar.classList.contains('w-20'));
        });
    }
</script>
</body>
</html>
