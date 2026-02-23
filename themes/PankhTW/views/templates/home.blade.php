@extends('layouts.main')

@section('content')
    @includeIf(theme_view('blocks.slider_bootstrap'))

    <section class="pankh-section">
        <div class="pankh-container space-y-6">
            @foreach(($blocks ?? []) as $block)
                @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
            @endforeach
        </div>
    </section>
@endsection
