@if(count($tree))
    @php
        // Helper to recursively render menu items with Bootstrap classes
        function renderMenuItems($items, $level = 0) {
            $html = '';
            foreach ($items as $item) {
                $hasChildren = !empty($item->children) && count($item->children) > 0;
                $isDropdown = $hasChildren && $level === 0;
                $liClass = $isDropdown ? 'nav-item dropdown' : 'nav-item';
                $aClass = 'nav-link' . ($isDropdown ? ' dropdown-toggle' : '');
                $aAttrs = $isDropdown ? ' data-bs-toggle="dropdown" role="button" aria-expanded="false"' : '';
                $url = $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : $item->url;
                $html .= '<div class="' . $liClass . '">';
                $html .= '<a href="' . $url . '" class="nav-link' . ($isDropdown ? ' dropdown-toggle' : '') . '"' . $aAttrs . '>' . e($item->title) . '</a>';
                if ($isDropdown) {
                    $html .= '<div class="dropdown-menu m-0">';
                    foreach ($item->children as $child) {
                        $childUrl = $child->page_id ? '/' . optional(App\Models\Page::find($child->page_id))->slug : $child->url;
                        $html .= '<a href="' . $childUrl . '" class="dropdown-item">' . e($child->title) . '</a>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            return $html;
        }
    @endphp
    {!! renderMenuItems($tree) !!}
@endif
