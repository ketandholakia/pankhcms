@php
    $left = $block['left'] ?? ($block['left_html'] ?? '');
    $right = $block['right'] ?? ($block['right_html'] ?? '');
@endphp
<section class="section-gap py-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6 content-block">{!! $left !!}</div>
            <div class="col-lg-6 content-block">{!! $right !!}</div>
        </div>
    </div>
</section>
