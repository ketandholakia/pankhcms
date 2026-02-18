@extends('layouts.site')

@section('content')
    @if(!empty($blocks))
        @foreach($blocks as $block)
            @includeIf('site.blocks.' . $block['type'], ['block' => $block])
        @endforeach
    @endif
@endsection
