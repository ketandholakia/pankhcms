{{-- Works with both block data shapes:
     string:  $data = ['text' => sanitized, 'title' => ..., 'show_title' => ...]
     array:   $data = ['title' => ..., 'text'/'html' => ..., 'members' => [[name, role, photo, bio]], ...] --}}
@php
    $title = (string) ($data['title'] ?? '');
    $showTitle = $data['show_title'] ?? true;
    $text = (string) ($data['text'] ?? $data['html'] ?? '');
    $members = $data['members'] ?? [];
@endphp
<section class="block-team section">
    <div class="container">
        @if($title !== '' && $showTitle)
            <h3 class="title is-3">{{ $title }}</h3>
        @endif

        @if($text !== '')
            <div class="content">{!! $text !!}</div>
        @endif

        @if(is_array($members) && count($members))
            <div class="columns is-multiline">
                @foreach($members as $member)
                    <div class="column is-6 is-3-desktop">
                        <div class="card team-card">
                            @if(!empty($member['photo']))
                                <img class="team-photo" src="{{ $member['photo'] }}" alt="{{ $member['name'] ?? '' }}" loading="lazy" decoding="async" width="200" height="200">
                            @endif
                            <div class="card-content">
                                <p class="title is-5">{{ $member['name'] ?? '' }}</p>
                                <p class="subtitle is-6">{{ $member['role'] ?? '' }}</p>
                                <p>{{ $member['bio'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
