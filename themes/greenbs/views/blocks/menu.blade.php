@if(count($tree))
    @php
        // Helper to recursively render menu items with Bootstrap classes
        function renderMenuItems($items, $level = 0) {
            $html = '';
            $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            foreach ($items as $item) {
                $hasChildren = !empty($item->children) && count($item->children) > 0;
                $isDropdown = $hasChildren && $level === 0;
                $url = $item->page_id ? '/' . optional(App\Models\Page::find($item->page_id))->slug : $item->url;
                $itemPath = rtrim($url, '/');
                $isActive = $itemPath === $currentPath;
                $liClass = $isDropdown ? 'nav-item dropdown' : 'nav-item';
                if ($isActive) $liClass .= ' active';
                $aClass = 'nav-link' . ($isDropdown ? ' dropdown-toggle' : '');
                if ($isActive) $aClass .= ' active';
                $aAttrs = $isDropdown ? ' data-bs-toggle="dropdown" role="button" aria-expanded="false"' : '';
                $html .= '<div class="' . $liClass . '">';
                $html .= '<a href="' . $url . '" class="' . $aClass . '"' . $aAttrs . '>' . e($item->title) . '</a>';
                if ($isDropdown) {
                    $html .= '<div class="dropdown-menu m-0">';
                    foreach ($item->children as $child) {
                        $childUrl = $child->page_id ? '/' . optional(App\Models\Page::find($child->page_id))->slug : $child->url;
                        $childPath = rtrim($childUrl, '/');
                        $isChildActive = $childPath === $currentPath;
                        $childClass = 'dropdown-item' . ($isChildActive ? ' active' : '');
                        $html .= '<a href="' . $childUrl . '" class="' . $childClass . '">' . e($child->title) . '</a>';
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
