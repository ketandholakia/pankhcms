@extends('layouts.main')

@section('content')

@php
    $publishedAt = $page->published_at ?? null;
    $categories = $page->categories ?? collect();
    $tags = $page->tags ?? collect();
@endphp

<article class="post-single section">
    <div class="container">
        <header class="post-header">
            <h1 class="title is-1">{{ $page->title }}</h1>

            <div class="post-meta">
                @if($publishedAt)
                    <time datetime="{{ $publishedAt->toIso8601String() }}">
                        {{ $publishedAt->format('F j, Y') }}
                    </time>
                @endif

                @if($categories->isNotEmpty())
                    <span class="post-meta-item">
                        @foreach($categories as $category)
                            <a class="tag" href="{{ url('/category/' . $category->slug) }}">{{ $category->name }}</a>
                        @endforeach
                    </span>
                @endif

                @if($tags->isNotEmpty())
                    <span class="post-meta-item">
                        @foreach($tags as $tag)
                            <a class="tag is-light" href="{{ url('/tag/' . $tag->slug) }}">{{ $tag->name }}</a>
                        @endforeach
                    </span>
                @endif
            </div>
        </header>

        @if(!empty($page->featured_image))
            <figure class="post-featured image">
                <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" loading="lazy" decoding="async">
            </figure>
        @endif

        @include('partials.breadcrumbs')

        <div class="content post-body">
            {!! sanitize_html((string) $page->content) !!}
        </div>

        {!! render_page_blocks($blocks ?? []) !!}
    </div>
</article>

@endsection
