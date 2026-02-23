@php
    $location = trim((string) ($block['location'] ?? 'header'));
@endphp
{!! render_menu($location ?: 'header') !!}
