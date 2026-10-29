@php
    $src = trim((string) ($block['src'] ?? ''));
    $alt = (string) ($block['alt'] ?? '');
@endphp
@if($src !== '')
<figure class="pb-block pb-block-image">
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" width="960" height="540">
    @if(!empty($block['caption']))
        <figcaption>{{ $block['caption'] }}</figcaption>
    @endif
</figure>
@endif
