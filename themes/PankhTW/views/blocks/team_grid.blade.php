@php
    $title = $block['title'] ?? 'Our Team';
    $members = is_array($block['members'] ?? null) ? $block['members'] : [];
@endphp
<section class="space-y-4">
    <h2 class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse($members as $member)
            <article class="pankh-card overflow-hidden">
                @if(!empty($member['image']))
                    <img src="{{ $member['image'] }}" class="h-40 w-full object-cover" alt="{{ $member['name'] ?? 'Member' }}">
                @endif
                <div class="p-4">
                    <h3 class="text-base font-semibold">{{ $member['name'] ?? 'Team Member' }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $member['role'] ?? '' }}</p>
                </div>
            </article>
        @empty
            <p class="text-sm text-slate-500">No team members configured.</p>
        @endforelse
    </div>
</section>
