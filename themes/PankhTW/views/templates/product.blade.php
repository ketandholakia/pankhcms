@extends('layouts.main')

@section('content')
    <section class="pankh-section">
        <div class="pankh-container">
            <div class="grid gap-8 lg:grid-cols-2">
                <div>
                    @if(!empty($page->featured_image))
                        <img src="{{ $page->featured_image }}" alt="{{ $page->title ?? 'Product' }}" class="h-auto w-full rounded-2xl border border-slate-200 object-cover shadow-sm">
                    @else
                        <div class="flex aspect-video items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white text-slate-500">
                            No image
                        </div>
                    @endif
                </div>
                <div class="space-y-4">
                    <h1 class="text-3xl font-semibold tracking-tight">{{ $page->title ?? '' }}</h1>
                    @if(!empty($page->meta_description))
                        <p class="text-slate-600">{{ $page->meta_description }}</p>
                    @endif
                    <div class="prose max-w-none prose-slate">{!! $page->content ?? '' !!}</div>
                </div>
            </div>

            @if(!empty($blocks))
                <div class="mt-10 space-y-6">
                    @foreach($blocks as $block)
                        @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
