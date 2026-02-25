<div class="block-cta text-center py-5">
    <h2>{{ $data['heading'] ?? '' }}</h2>
    <p>{{ $data['subheading'] ?? '' }}</p>
    @if(!empty($data['button_text']) && !empty($data['button_link']))
        <a href="{{ $data['button_link'] }}" class="btn btn-primary mt-3">{{ $data['button_text'] }}</a>
    @endif
</div>
