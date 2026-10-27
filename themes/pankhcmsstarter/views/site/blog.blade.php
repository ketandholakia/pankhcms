@extends('layouts.main')

@section('content')

<section class="section blog-index">
    <div class="container">
        <h1 class="title is-1">Blog</h1>

        @if($posts->isEmpty())
            <div class="notification is-light">
                No posts have been published yet. Check back soon.
            </div>
        @else
            <div class="columns is-multiline">
                @foreach($posts as $post)
                    @php
                        $excerpt = \Illuminate\Support\Str::limit(
                            trim(strip_tags((string) ($post->content ?? ''))),
                            180
                        );
                        $publishedAt = $post->published_at;
                    @endphp
                    <article class="column is-6 is-4-widescreen">
                        <div class="card post-card">
                            @if(!empty($post->featured_image))
                                <a href="{{ url('/' . $post->slug) }}" class="card-image">
                                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                                         loading="lazy" decoding="async" width="640" height="360">
                                </a>
                            @endif
                            <div class="card-content">
                                <p class="post-card-date">
                                    @if($publishedAt)
                                        <time datetime="{{ $publishedAt->toIso8601String() }}">{{ $publishedAt->format('F j, Y') }}</time>
                                    @endif
                                </p>
                                <h2 class="title is-4">
                                    <a href="{{ url('/' . $post->slug) }}">{{ $post->title }}</a>
                                </h2>
                                @if($excerpt !== '')
                                    <p class="post-card-excerpt">{{ $excerpt }}</p>
                                @endif
                                <a class="button is-light is-small" href="{{ url('/' . $post->slug) }}">Read more</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if(!empty($paginator) && $paginator['last'] > 1)
                <nav class="pagination is-centered" aria-label="pagination">
                    @php $params = $_GET ?? []; unset($params['page']); @endphp
                    @if($paginator['current'] > 1)
                        <a class="pagination-previous"
                           href="{{ url('/blog?' . http_build_query(array_merge($params, ['page' => $paginator['current'] - 1]))) }}">&laquo; Newer</a>
                    @endif
                    @if($paginator['current'] < $paginator['last'])
                        <a class="pagination-next"
                           href="{{ url('/blog?' . http_build_query(array_merge($params, ['page' => $paginator['current'] + 1]))) }}">Older &raquo;</a>
                    @endif
                    <span class="pagination-current">Page {{ $paginator['current'] }} of {{ $paginator['last'] }}</span>
                </nav>
            @endif
        @endif
    </div>
</section>

@endsection
