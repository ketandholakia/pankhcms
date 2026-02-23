@php
    $html = $block['html'] ?? ($block['content'] ?? '');
@endphp
<section class="pankh-card p-5 sm:p-6">
    <div class="prose max-w-none prose-slate">{!! $html !!}</div>
</section>
