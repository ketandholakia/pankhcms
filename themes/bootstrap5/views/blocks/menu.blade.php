@php($menuItems = menu_tree($block['menu_slug'] ?? 'header'))

@if(count($menuItems))
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Quick Links</h2>
            @include('partials.menu-bootstrap5', ['items' => $menuItems])
        </div>
    </div>
@endif
