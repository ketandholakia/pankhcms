<section class="py-4">
    <div class="container">
        <h2>Contact Information</h2>
        <ul class="list-unstyled">
            <li><strong>Address:</strong> {{ $block['data']['address'] ?? '' }}</li>
            <li><strong>Phone:</strong> {{ $block['data']['phone'] ?? '' }}</li>
            <li><strong>Email:</strong> <a href="mailto:{{ $block['data']['email'] ?? '' }}">{{ $block['data']['email'] ?? '' }}</a></li>
        </ul>
    </div>
</section>
