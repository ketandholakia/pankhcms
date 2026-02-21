@extends('layouts.main')

@section('content')
    @foreach($blocks as $block)
        @php
            $type = $block['type'] ?? '';
            $partial = 'blocks.' . $type;
        @endphp
        @includeIf($partial, ['block' => $block])
    @endforeach

    


@endsection
