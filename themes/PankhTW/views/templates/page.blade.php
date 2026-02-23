@extends('layouts.main')

@section('content')
    <section class="pankh-section">
        <div class="pankh-container space-y-6">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $page->title ?? '' }}</h1>
            @foreach(($blocks ?? []) as $block)
                @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
            @endforeach
        </div>
    </section>
@endsection
