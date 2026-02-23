@extends('layouts.main')

@section('content')
    <section class="section-gap">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    @if(!empty($page->featured_image))
                        <img src="{{ $page->featured_image }}" alt="{{ $page->title ?? 'Product' }}" class="img-fluid rounded-3 shadow-sm w-100 card-media">
                    @else
                        <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center card-media">
                            <span class="text-muted">No Image</span>
                        </div>
                    @endif
                </div>
                <div class="col-lg-7">
                    <h1 class="mb-3">{{ $page->title ?? '' }}</h1>
                    @if(!empty($page->meta_description))
                        <p class="lead">{{ $page->meta_description }}</p>
                    @endif
                    <div class="content-block">{!! $page->content ?? '' !!}</div>
                </div>
            </div>

            @if(!empty($blocks))
                <div class="mt-5">
                    @foreach($blocks as $block)
                        @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
