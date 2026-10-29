@php
    // Server-side validation (PageController) enforces the YouTube/Vimeo
    // allowlist; this view additionally normalizes to the embed URL.
    $raw = trim((string) ($block['url'] ?? ''));
    $src = '';
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,20})#i', $raw, $m)) {
        $src = 'https://www.youtube-nocookie.com/embed/' . $m[1];
    } elseif (preg_match('#vimeo\.com/(\d{6,12})#i', $raw, $m)) {
        $src = 'https://player.vimeo.com/video/' . $m[1];
    }
@endphp
@if($src !== '')
<figure class="pb-block pb-block-video">
    <div class="video-embed">
        <iframe src="{{ $src }}" title="{{ $block['caption'] ?? 'Embedded video' }}"
                loading="lazy" allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
                allow="encrypted-media; picture-in-picture"></iframe>
    </div>
    @if(!empty($block['caption']))
        <figcaption>{{ $block['caption'] }}</figcaption>
    @endif
</figure>
@endif
