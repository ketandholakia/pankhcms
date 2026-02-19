<li>
    <div class="flex items-center mb-1">
        <span class="font-semibold">{{ $item->title }}</span>
        <span class="ml-2 text-gray-500 text-xs">
            @if($item->page_id)
                (Page)
            @elseif($item->url)
                ({{ $item->url }})
            @endif
        </span>
        <button 
            type="button" 
            class="bg-yellow-400 text-white px-2 py-1 rounded text-xs ml-2 js-menu-item-edit-btn"
            data-id="{{ $item->id }}"
            data-title="{{ $item->title }}"
            data-url="{{ $item->url }}"
            data-page-id="{{ $item->page_id }}"
            data-parent-id="{{ $item->parent_id }}"
            data-sort-order="{{ $item->sort_order }}"
        >Edit</button>
        <form action="/admin/menu-items/{{ $item->id }}/delete" method="POST" class="inline ml-2 js-menu-item-delete">
            <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded text-xs">Delete</button>
        </form>
        <form action="/admin/menu-items/{{ $item->id }}/move" method="POST" class="inline ml-1">
            <input type="hidden" name="direction" value="up">
            <button type="submit" class="bg-gray-300 text-xs px-2 py-1 rounded">&#8593;</button>
        </form>
        <form action="/admin/menu-items/{{ $item->id }}/move" method="POST" class="inline ml-1">
            <input type="hidden" name="direction" value="down">
            <button type="submit" class="bg-gray-300 text-xs px-2 py-1 rounded">&#8595;</button>
        </form>
    </div>
    @if(!empty($item->children))
        <ul class="ml-6">
            @foreach($item->children as $child)
                @include('admin.menus.menu_item', ['item' => $child])
            @endforeach
        </ul>
    @endif
</li>
