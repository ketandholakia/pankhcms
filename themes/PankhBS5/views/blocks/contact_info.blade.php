@php
    $title = $block['title'] ?? 'Contact Information';
    $address = $block['address'] ?? setting('company_address', '');
    $phone = $block['phone'] ?? setting('company_phone', '');
    $email = $block['email'] ?? setting('company_email', '');
    $map = $block['map_embed_url'] ?? ($contact_map_embed_url ?? '');
@endphp
<div class="card border-0 shadow-sm h-100">
    <div class="card-body p-4">
        <h3 class="h5 mb-3">{{ $title }}</h3>
        @if($address !== '')
            <p class="mb-2"><strong>Address:</strong> {{ $address }}</p>
        @endif
        @if($phone !== '')
            <p class="mb-2"><strong>Phone:</strong> <a href="tel:{{ $phone }}">{{ $phone }}</a></p>
        @endif
        @if($email !== '')
            <p class="mb-3"><strong>Email:</strong> <a href="mailto:{{ $email }}">{{ $email }}</a></p>
        @endif

        @if($map !== '')
            <div class="ratio ratio-16x9 rounded overflow-hidden">
                <iframe src="{{ $map }}" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        @endif
    </div>
</div>
