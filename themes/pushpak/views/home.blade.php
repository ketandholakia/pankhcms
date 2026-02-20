@extends('layouts.main')

@section('content')
    <h1 class="mb-4">Welcome to Pushpak Theme</h1>
    @foreach($blocks as $block)
        @include('blocks.' . $block['type'], ['block' => $block])
    @endforeach
@endsection
