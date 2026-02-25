<div class="block-text">
    @if(!empty($data['title']) && ($data['show_title'] ?? true))
        <h5>{{ $data['title'] }}</h5>
    @endif
    <div>{!! $data['text'] ?? '' !!}</div>
</div>
