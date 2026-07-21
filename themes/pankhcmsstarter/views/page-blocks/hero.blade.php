@php
    $title = trim((string) ($block['title'] ?? ''));
@endphp

@if($title !== '')
    <section class="hero is-medium is-link pb-block pb-block-hero">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title">{{ $title }}</h1>
            </div>
        </div>
    </section>
@endif
