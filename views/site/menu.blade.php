@if(count($tree))
<ul>
    @foreach($tree as $item)
        <li>
            <a href="{{ $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : $item->url }}" class="hover:underline">
                {{ $item->title }}
            </a>
            @if(!empty($item->children))
                <ul class="ml-4">
                    @foreach($item->children as $child)
                        <li>
                            <a href="{{ $child->page_id ? '/' . optional(App\Models\Page::find($child->page_id))->slug : $child->url }}" class="hover:underline">
                                {{ $child->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
@endif
