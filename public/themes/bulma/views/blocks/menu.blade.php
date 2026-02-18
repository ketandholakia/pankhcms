@php($tree = menu_tree($block['menu_slug'] ?? 'header'))

@if(count($tree))
    <div class="box">
        <p class="menu-label">Quick Links</p>
        <aside class="menu">
            <ul class="menu-list">
                @foreach($tree as $item)
                    <li>
                        <a href="{{ $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : $item->url }}">
                            {{ $item->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
@endif
