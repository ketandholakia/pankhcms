@php($menuItems = menu_tree($block['menu_slug'] ?? 'header'))

@if(count($menuItems))
    <div class="box">
        <p class="menu-label">Quick Links</p>
        <aside class="menu">
            @include('partials.menu-bulma', ['items' => $menuItems])
        </aside>
    </div>
@endif
