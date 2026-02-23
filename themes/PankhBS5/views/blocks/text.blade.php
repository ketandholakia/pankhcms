@php
    $html = $block['html'] ?? ($block['content'] ?? '');
@endphp
<section class="section-gap py-3">
    <div class="container content-block">
        {!! $html !!}
    </div>
</section>
