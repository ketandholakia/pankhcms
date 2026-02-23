    
<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
        <style>
        /* Ensure Lucide icons are always visible in collapsed sidebar */
        #admin-sidebar .sidebar-link i[data-lucide] {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        #admin-sidebar.w-20 .sidebar-label {
            display: none !important;
        }
        </style>
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
        <div class="mb-4">
            <input id="sidebar-search" type="text" placeholder="Search menu... ({{ setting('sidebar_search_shortcut', 'Ctrl+Shift+F') }})" class="w-full px-3 py-2 rounded bg-gray-800 text-gray-100 placeholder-gray-400 focus:outline-none focus:bg-gray-700" autocomplete="off">
        </div>

        @php
        $sidebarGroups = [
            'General' => [
                ["/admin", "layout-dashboard", "Dashboard"],
                ["/admin/profile", "user-cog", "My Profile"],
            ],
            'Content Management' => [
                ["/admin/pages", "file-text", "Pages"],
                ["/admin/content-types", "shapes", "Content Types"],
                ["/admin/categories", "folder", "Categories"],
                ["/admin/tags", "tag", "Tags"],
                ["/admin/media", "image", "Media"],
                ["/admin/slider", "image", "Slider Images"],
            ],
            'Design' => [
                ["/admin/templates", "layout", "Templates"],
                ["/admin/themes", "palette", "Themes"],
                ["/admin/menus", "menu", "Menus"],
            ],
            'Communication' => [
                ["/admin/messages", "inbox", "Messages"],
            ],
            'System' => [
                ["/admin/settings", "settings", "Settings"],
                ["/admin/settings/seo", "search", "SEO Settings"],
                ["/admin/settings/breadcrumbs", "chevron-right-square", "Breadcrumbs"],
                ["/admin/backups", "database-backup", "Backups"],
            ],
        ];
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        @endphp
        <nav>
            @foreach ($sidebarGroups as $group => $links)
                <div class="mb-2">
                    <button type="button" class="w-full flex items-center justify-between px-2 py-1 text-xs font-bold uppercase tracking-wide text-gray-400 group-toggle focus:outline-none" data-group="{{ $group }}">
                        <span>{{ $group }}</span>
                        <i data-lucide="chevron-down"></i>
                    </button>
                    <div class="sidebar-group-links space-y-1" data-group="{{ $group }}">
                        @foreach ($links as $link)
                            @php
                                $href = $link[0];
                                $icon = $link[1];
                                $label = $link[2];
                                $isActive = strpos($currentUrl, $href) === 0;
                                $activeClass = $isActive ? ' bg-blue-900 text-white font-semibold' : '';
                            @endphp
                            <a href="{{ $href }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-800{{ $activeClass }}">
                                <i data-lucide="{{ $icon }}"></i>
                                <span class="sidebar-label">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
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
                <a href="/admin/profile" class="flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600">
                    <i data-lucide="user"></i>
                    {{ $displayName }}
                </a>
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
        if (!sidebar) return;
        sidebar.classList.toggle('w-64', !collapsed);
        sidebar.classList.toggle('w-20', collapsed);
        sidebarLabels.forEach((label) => label.classList.toggle('hidden', collapsed));
        sidebarLinks.forEach((link) => {
            link.classList.toggle('justify-center', collapsed);
            link.classList.toggle('px-2', collapsed);
            link.classList.toggle('px-3', !collapsed);
            // Ensure icon is always visible
            const icon = link.querySelector('i[data-lucide]');
            if (icon) {
                icon.style.display = 'inline-block';
                icon.style.visibility = 'visible';
                icon.style.opacity = '1';
                icon.classList.remove('hidden');
            }
        });
        // Hide group links if collapsed
        document.querySelectorAll('.sidebar-group-links').forEach((group) => {
            group.classList.toggle('hidden', collapsed);
        });
        document.querySelectorAll('.group-toggle').forEach((btn) => {
            btn.classList.toggle('hidden', collapsed);
        });
        // Force lucide icons to re-render after DOM changes
        setTimeout(function() {
            if (window.lucide && typeof lucide.createIcons === 'function') {
                lucide.createIcons();
            }
        }, 10);
    }
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setSidebarCollapsed(!sidebar.classList.contains('w-20'));
        });
    }

    // Collapsible groups
    document.querySelectorAll('.group-toggle').forEach((btn) => {
        btn.addEventListener('click', function () {
            const group = this.getAttribute('data-group');
            const links = document.querySelector(`.sidebar-group-links[data-group="${group}"]`);
            if (links) {
                links.classList.toggle('hidden');
                this.querySelector('i').classList.toggle('rotate-180');
            }
        });
    });

    // Sidebar search/filter
    const sidebarSearch = document.getElementById('sidebar-search');
    if (sidebarSearch) {
        sidebarSearch.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.sidebar-group-links').forEach((group) => {
                let anyVisible = false;
                group.querySelectorAll('.sidebar-link').forEach((link) => {
                    const label = link.querySelector('.sidebar-label')?.textContent.toLowerCase() || '';
                    const visible = !query || label.includes(query);
                    link.classList.toggle('hidden', !visible);
                    if (visible) anyVisible = true;
                });
                // Hide group if no links are visible
                group.parentElement.classList.toggle('hidden', !anyVisible);
            });
        });
        // Keyboard shortcut: configurable via settings (e.g. "Ctrl+Shift+F")
        const shortcutRaw = "{{ addslashes(setting('sidebar_search_shortcut', 'Ctrl+Shift+F')) }}";

        function parseShortcut(input) {
            const parts = String(input || '').split('+').map(p => p.trim()).filter(Boolean);
            const mods = { ctrl: false, shift: false, alt: false, meta: false };
            let key = '';
            for (const part of parts) {
                const p = part.toLowerCase();
                if (p === 'ctrl' || p === 'control') mods.ctrl = true;
                else if (p === 'shift') mods.shift = true;
                else if (p === 'alt' || p === 'option') mods.alt = true;
                else if (p === 'meta' || p === 'cmd' || p === 'command' || p === 'win') mods.meta = true;
                else key = part;
            }
            key = key || 'F';
            return { ...mods, key };
        }

        const shortcut = parseShortcut(shortcutRaw);

        function normalizeKeyName(k) {
            const key = String(k || '').toLowerCase();
            if (key === 'slash') return '/';
            if (key === 'space') return ' ';
            if (key.length === 1) return key;
            return key;
        }

        document.addEventListener('keydown', function (e) {
            const keyWanted = normalizeKeyName(shortcut.key);
            const pressed = normalizeKeyName(e.key);
            const match =
                pressed === keyWanted &&
                (!!e.ctrlKey === !!shortcut.ctrl) &&
                (!!e.shiftKey === !!shortcut.shift) &&
                (!!e.altKey === !!shortcut.alt) &&
                (!!e.metaKey === !!shortcut.meta);

            if (!match) return;
            if (document.activeElement === sidebarSearch) return;

            e.preventDefault();
            sidebarSearch.focus();
            sidebarSearch.select();
        });
    }
</script>
</body>
</html>
