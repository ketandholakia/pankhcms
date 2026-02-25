<div class="block-social-links">
    <h5>{{ $data['title'] ?? 'Follow Us' }}</h5>
    <div>
        @foreach(($data['links'] ?? []) as $link)
            <a href="{{ $link['url'] }}" target="_blank" class="me-2">
                <i class="bi bi-{{ $link['icon'] ?? 'link' }}"></i>
            </a>
        @endforeach
    </div>
</div>
