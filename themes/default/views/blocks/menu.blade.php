@if(!empty($tree) && count($tree))
<ul>
  @foreach($tree as $item)
    @php
      $href = $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : $item->url;
      $hasChildren = !empty($item->children) && count($item->children) > 0;
    @endphp
    <li>
      <a href="{{ $href }}">{{ $item->title }}</a>
      @if($hasChildren)
        @include('blocks.menu', ['tree' => $item->children])
      @endif
    </li>
  @endforeach
</ul>
@endif
