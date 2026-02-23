@extends('layouts.main')

@section('content')
    @includeIf(theme_view('blocks.slider_bootstrap'))

    <section class="section-gap bg-white">
        <div class="container">
            @foreach($blocks as $block)
                @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
            @endforeach
        </div>
    </section>
@endsection
