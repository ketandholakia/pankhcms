@extends('layouts.main')

@section('content')
    @foreach($blocks as $block)
        @include('blocks.' . $block['type'], ['block' => $block])
    @endforeach
@endsection
