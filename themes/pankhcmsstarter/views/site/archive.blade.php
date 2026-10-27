@extends('layouts.main')

@section('content')

<section class="section archive-page">
    <div class="container">
        <h1 class="title is-2">
            {{ $taxonomy === 'category' ? 'Category' : 'Tag' }}: {{ $term->name ?? $term->title ?? $term->slug }}
        </h1>
        <p class="subtitle">{{ $paginator['total'] }} item{{ $paginator['total'] === 1 ? '' : 's' }}</p>

        @if($pages->isEmpty())
            <div class="notification is-light">Nothing published here yet.</div>
        @else
            <div class="columns is-multiline">
                @foreach($pages as $item)
                    @php
                        $excerpt = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($item->content ?? ''))), 160);
                        $publishedAt = $item->published_at;
                    @endphp
                    <article class="column is-6 is-4-widescreen">
                        <div class="card post-card">
                            @if(!empty($item->featured_image))
                                <a href="{{ url('/' . $item->slug) }}" class="card-image">
                                    <img src="{{ $item->featured_image }}" alt="{{ $item->title }}"
                                         loading="lazy" decoding="async" width="640" height="360">
                                </a>
                            @endif
                            <div class="card-content">
                                @if($publishedAt)
                                    <p class="post-card-date">
                                        <time datetime="{{ $publishedAt->toIso8601String() }}">{{ $publishedAt->format('F j, Y') }}</time>
                                    </p>
                                @endif
                                <h2 class="title is-4"><a href="{{ url('/' . $item->slug) }}">{{ $item->title }}</a></h2>
                                @if($excerpt !== '')
                                    <p class="post-card-excerpt">{{ $excerpt }}</p>
                                @endif
                                <a class="button is-light is-small" href="{{ url('/' . $item->slug) }}">Read more</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($paginator['last'] > 1)
                <nav class="pagination is-centered" aria-label="pagination">
                    @php $base = url('/' . $taxonomy . '/' . $slug); @endphp
                    @if($paginator['current'] > 1)
                        <a class="pagination-previous" href="{{ $base . '?' . http_build_query(['page' => $paginator['current'] - 1]) }}">&laquo; Newer</a>
                    @endif
                    @if($paginator['current'] < $paginator['last'])
                        <a class="pagination-next" href="{{ $base . '?' . http_build_query(['page' => $paginator['current'] + 1]) }}">Older &raquo;</a>
                    @endif
                    <span class="pagination-current">Page {{ $paginator['current'] }} of {{ $paginator['last'] }}</span>
                </nav>
            @endif
        @endif
    </div>
</section>

@endsection
