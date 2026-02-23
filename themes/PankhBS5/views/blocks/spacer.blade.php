@php
    $height = (int) ($block['height'] ?? 40);
    if ($height < 8) $height = 8;
    if ($height > 240) $height = 240;
@endphp
<div style="height: {{ $height }}px;"></div>
