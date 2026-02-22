<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="flex">

    <aside id="admin-sidebar" class="w-64 bg-gray-900 text-gray-200 p-4 min-h-screen transition-all duration-200">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <img src="/assets/pankhcms_logo_dark.png" alt="CMS Logo" class="h-20 w-auto sidebar-label" style="max-height:80px;">
            </h2>

            <button id="sidebar-toggle" type="button" class="p-2 rounded hover:bg-gray-800" aria-label="Toggle sidebar">
                <i data-lucide="panel-left-close"></i>
            </button>
        </div>

        @php
            $sidebarLinks = [
                ["/admin", "layout-dashboard", "Dashboard"],
                ["/admin/pages", "file-text", "Pages"],
                ["/admin/content-types", "shapes", "Content Types"],
                ["/admin/messages", "inbox", "Messages"],
                ["/admin/categories", "folder", "Categories"],
                ["/admin/tags", "tag", "Tags"],
                ["/admin/slider", "image", "Slider Images"],
                ["/admin/templates", "layout", "Templates"],
                ["/admin/themes", "palette", "Themes"],
                ["/admin/backups", "database-backup", "Backups"],
                ["/admin/settings", "settings", "Settings"],
                ["/admin/settings/seo", "search", "SEO Settings", true],
                ["/admin/settings/breadcrumbs", "chevron-right-square", "Breadcrumbs"],
                ["/admin/menus", "menu", "Menus"],
                ["/admin/media", "image", "Media"],
            ];
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        @endphp
        <nav class="space-y-1">
            @foreach ($sidebarLinks as $link)
                @php
                    $href = $link[0];
                    $icon = $link[1];
                    $label = $link[2];
                    $isSeo = $link[3] ?? false;
                    $isActive = strpos($currentUrl, $href) === 0;
                    $activeClass = $isActive ? ' bg-blue-900 text-white font-semibold' : '';
                @endphp
                <a href="{{ $href }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800{{ $activeClass }}">
                    <i data-lucide="{{ $icon }}"></i>
                    <span class="sidebar-label">{{ $label }}</span>
                    @if ($isSeo && $isActive)
                        <i data-lucide="check-circle" class="text-green-400"></i>
                    @endif
                </a>
            @endforeach
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
                    {!! csrf_field() !!}
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
    // CSRF helpers for admin UI (forms + fetch + XMLHttpRequest)
    window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.addEventListener('DOMContentLoaded', () => {
        if (!window.CSRF_TOKEN) return;

        // Ensure all POST forms include the CSRF token
        document.querySelectorAll('form').forEach((form) => {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method !== 'POST') return;
            if (form.querySelector('input[name="_csrf"]')) return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf';
            input.value = window.CSRF_TOKEN;
            form.appendChild(input);
        });
    });

    // Auto-add CSRF header for same-origin fetch
    if (window.fetch && window.CSRF_TOKEN) {
        const _fetch = window.fetch.bind(window);
        window.fetch = (input, init = {}) => {
            const method = (init.method || (input && input.method) || 'GET').toString().toUpperCase();
            if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
                const headers = new Headers(init.headers || (input && input.headers) || undefined);
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', window.CSRF_TOKEN);
                }
                init.headers = headers;
            }
            return _fetch(input, init);
        };
    }

    // Auto-add CSRF header for XMLHttpRequest (TinyMCE uploads, etc.)
    if (window.XMLHttpRequest && window.CSRF_TOKEN) {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
            this.__csrfMethod = (method || 'GET').toString().toUpperCase();
            this.__csrfUrl = url;
            return origOpen.call(this, method, url, async, user, password);
        };

        XMLHttpRequest.prototype.send = function (body) {
            try {
                if (this.__csrfMethod && !['GET', 'HEAD', 'OPTIONS'].includes(this.__csrfMethod)) {
                    this.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN);
                }
            } catch (e) {
                // ignore header set failures
            }
            return origSend.call(this, body);
        };
    }

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
