<section class="hero is-primary is-small">
    <div class="hero-body">
        <div class="container">
            <h1 class="title">{{ $page->title ?? 'Welcome' }}</h1>
            @if(!empty($page->meta_description))
                <p class="subtitle">{{ $page->meta_description }}</p>
            @endif
        </div>
    </div>
</section>
