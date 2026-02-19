@php
    use App\Models\Page;
@endphp

@if(count($tree))
    @foreach($tree as $item)
        @php
            $itemUrl = $item->page_id ? '/' . optional(Page::find($item->page_id))->slug : ($item->url ?: '#');
            $hasChildren = !empty($item->children);
            $dropdownId = 'menuDropdown' . $item->id;
        @endphp

        @if($hasChildren)
            <div class="nav-item dropdown">
                <div class="d-flex align-items-center">
                    <a class="nav-link" href="{{ $itemUrl }}">{{ $item->title }}</a>
                    <a class="nav-link dropdown-toggle" href="#" id="{{ $dropdownId }}" role="button" data-bs-toggle="dropdown" aria-expanded="false"></a>
                </div>

                <ul class="dropdown-menu" aria-labelledby="{{ $dropdownId }}">
                    @foreach($item->children as $child)
                        @php
                            $childUrl = $child->page_id ? '/' . optional(Page::find($child->page_id))->slug : ($child->url ?: '#');
                        @endphp
                        <li>
                            <a class="dropdown-item" href="{{ $childUrl }}">
                                {{ $child->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <a class="nav-link" href="{{ $itemUrl }}">
                {{ $item->title }}
            </a>
        @endif
    @endforeach
@endif
