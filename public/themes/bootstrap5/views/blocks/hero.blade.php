<section class="py-5 text-white hero-gradient">
    <div class="container py-4">
        <h1 class="display-5 fw-bold mb-2">{{ $page->title ?? 'Welcome' }}</h1>
        @if(!empty($page->meta_description))
            <p class="lead mb-0">{{ $page->meta_description }}</p>
        @endif
    </div>
</section>
