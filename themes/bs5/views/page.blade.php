@extends('layouts.main')

@section('content')

    @php
        $blocks = json_decode($page->content_json ?? '[]', true);
    @endphp

    @foreach ($blocks as $block)

        @includeIf('blocks.' . $block['type'], $block)

    @endforeach

@endsection
