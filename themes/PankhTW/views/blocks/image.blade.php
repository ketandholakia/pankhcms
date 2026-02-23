@php
    $src = $block['src'] ?? ($block['url'] ?? '');
    $alt = $block['alt'] ?? '';
    $caption = $block['caption'] ?? '';
@endphp
@if($src !== '')
<figure class="space-y-2">
    <img src="{{ $src }}" alt="{{ $alt }}" class="w-full rounded-xl border border-slate-200 object-cover shadow-sm">
    @if($caption !== '')
        <figcaption class="text-sm text-slate-600">{{ $caption }}</figcaption>
    @endif
</figure>
@endif
