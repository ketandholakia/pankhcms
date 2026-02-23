@extends('layouts.main')

@section('content')
    @foreach(($blocks ?? []) as $block)
        @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
    @endforeach
@endsection
