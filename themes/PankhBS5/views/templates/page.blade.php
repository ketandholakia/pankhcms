@extends('layouts.main')

@section('content')
    <section class="section-gap">
        <div class="container">
            <h1 class="mb-4">{{ $page->title ?? '' }}</h1>
            @foreach($blocks as $block)
                @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
            @endforeach
        </div>
    </section>
@endsection
