@php
    $title = $block['title'] ?? 'Our Team';
    $members = is_array($block['members'] ?? null) ? $block['members'] : [];
@endphp
<section class="section-gap">
    <div class="container">
        <h2 class="h3 mb-4">{{ $title }}</h2>
        <div class="row g-4">
            @forelse($members as $member)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        @if(!empty($member['image']))
                            <img src="{{ $member['image'] }}" class="card-img-top card-media" alt="{{ $member['name'] ?? 'Member' }}">
                        @endif
                        <div class="card-body">
                            <h3 class="h6 mb-1">{{ $member['name'] ?? 'Team Member' }}</h3>
                            <p class="text-muted mb-0">{{ $member['role'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted mb-0">No team members configured.</p></div>
            @endforelse
        </div>
    </div>
</section>
