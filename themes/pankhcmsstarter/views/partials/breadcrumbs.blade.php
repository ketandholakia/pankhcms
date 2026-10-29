@php
    // generateBreadcrumbs() returns ['title' => ..., 'url' => ...] entries;
    // the last entry is the current page.
    $crumbs = $breadcrumbs ?? [];
@endphp
<nav class="breadcrumb" aria-label="breadcrumbs">
    <ul>
        @forelse($crumbs as $index => $crumb)
            @php
                $isLast = $index === count($crumbs) - 1;
                $crumbUrl = (string) ($crumb['url'] ?? '');
                $isExternal = preg_match('#^https?://#i', $crumbUrl) === 1;
            @endphp
            <li class="{{ $isLast ? 'is-active' : '' }}">
                @if(!$isLast && $crumbUrl !== '')
                    <a href="{{ $isExternal ? $crumbUrl : url($crumbUrl) }}">{{ $crumb['title'] ?? '' }}</a>
                @else
                    <a href="#" aria-current="page">{{ $crumb['title'] ?? '' }}</a>
                @endif
            </li>
        @empty
            <li><a href="{{ url('/') }}">Home</a></li>
            <li class="is-active">
                <a href="#" aria-current="page">{{ $page->title ?? '' }}</a>
            </li>
        @endforelse
    </ul>
</nav>
