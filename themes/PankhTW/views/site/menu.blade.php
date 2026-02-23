@if(count($tree))
<nav>
    <ul class="flex flex-col gap-1 lg:flex-row lg:items-center lg:gap-2">
        @foreach($tree as $item)
            @php
                $itemUrl = $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : ($item->url ?? '#');
                $hasChildren = !empty($item->children) && count($item->children) > 0;
            @endphp
            <li class="relative">
                @if($hasChildren)
                    <details class="group">
                        <summary class="cursor-pointer list-none rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            {{ $item->title }}
                        </summary>
                        <ul class="mt-1 space-y-1 rounded-xl border border-slate-200 bg-white p-2 lg:absolute lg:min-w-48 lg:shadow-lg">
                            <li><a href="{{ $itemUrl }}" class="block rounded-md px-2 py-1 text-sm text-slate-700 hover:bg-slate-100">{{ $item->title }}</a></li>
                            @foreach($item->children as $child)
                                @php
                                    $childUrl = $child->page_id ? '/' . optional(App\Models\Page::find($child->page_id))->slug : ($child->url ?? '#');
                                @endphp
                                <li><a href="{{ $childUrl }}" class="block rounded-md px-2 py-1 text-sm text-slate-700 hover:bg-slate-100">{{ $child->title }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                @else
                    <a href="{{ $itemUrl }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ $item->title }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@endif
