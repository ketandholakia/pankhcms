@extends('layouts.main')

@section('content')
    <h1 class="mb-4">Welcome to Pushpak Theme</h1>
    @foreach($blocks as $block)
        @php
            $type = $block['type'] ?? '';
            $partial = 'blocks.' . $type;
        @endphp
        @includeIf($partial, ['block' => $block])
    @endforeach
@endsection
