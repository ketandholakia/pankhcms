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

        <?php
            $sidebarLinks = [
                ["/admin", "layout-dashboard", "Dashboard"],
                ["/admin/pages", "file-text", "Pages"],
                ["/admin/content-types", "shapes", "Content Types"],
                ["/admin/messages", "inbox", "Messages"],
                ["/admin/categories", "folder", "Categories"],
                ["/admin/tags", "tag", "Tags"],
                ["/admin/templates", "layout", "Templates"],
                ["/admin/themes", "palette", "Themes"],
                ["/admin/backups", "database-backup", "Backups"],
                ["/admin/settings/seo", "search", "SEO Settings", true],
                ["/admin/settings/breadcrumbs", "chevron-right-square", "Breadcrumbs"],
                ["/admin/menus", "menu", "Menus"],
                ["/admin/media", "image", "Media"],
            ];
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        ?>
        <nav class="space-y-1">
            <?php foreach ($sidebarLinks as $link):
                $href = $link[0];
                $icon = $link[1];
                $label = $link[2];
                $isSeo = $link[3] ?? false;
                $isActive = strpos($currentUrl, $href) === 0;
                $activeClass = $isActive ? ' bg-blue-900 text-white font-semibold' : '';
            ?>
                <a href="<?= $href ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800<?= $activeClass ?>">
                    <i data-lucide="<?= $icon ?>"></i>
                    <span class="sidebar-label"><?= $label ?></span>
                    <?php if ($isSeo && $isActive): ?>
                        <i data-lucide="check-circle" class="text-green-400"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
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
