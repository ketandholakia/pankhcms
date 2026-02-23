@php
    $left = $block['left'] ?? '';
    $right = $block['right'] ?? '';
@endphp
<section class="grid gap-4 md:grid-cols-2">
    <div class="pankh-card p-5"><div class="prose max-w-none prose-slate">{!! $left !!}</div></div>
    <div class="pankh-card p-5"><div class="prose max-w-none prose-slate">{!! $right !!}</div></div>
</section>
