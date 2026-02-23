@extends('layouts.main')

@section('content')
    @if(($page->slug ?? '') === 'product-gallery' || ($page->type ?? '') === 'gallery')
        @includeIf(theme_view('blocks.product_gallery'))
    @endif

    @foreach($blocks as $block)
        @php
            $type = $block['type'] ?? '';
            $partial = 'blocks.' . $type;
        @endphp
        @includeIf(theme_view($partial), ['block' => $block])
    @endforeach
@endsection
