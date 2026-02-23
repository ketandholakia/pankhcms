@php
    $content = $content ?? ($block['content'] ?? '');
@endphp
<section class="section-gap py-3">
    <div class="container content-block">
        {!! $content !!}
    </div>
</section>
