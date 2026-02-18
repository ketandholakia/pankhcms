@php($tree = menu_tree($block['menu_slug'] ?? 'header'))

@if(count($tree))
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Quick Links</h2>
            <ul class="list-group list-group-flush">
                @foreach($tree as $item)
                    <li class="list-group-item px-0">
                        <a class="text-decoration-none" href="{{ $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : $item->url }}">
                            {{ $item->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
