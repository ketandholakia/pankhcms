@extends('layouts.main')

@section('content')

<section class="section search-page">
    <div class="container">
        <h1 class="title is-2">Search</h1>

        <form class="search-form" method="GET" action="{{ url('/search') }}" role="search">
            <div class="field has-addons">
                <div class="control is-expanded">
                    <input class="input" type="search" name="q" value="{{ $q }}"
                           placeholder="Search the site…" aria-label="Search query">
                </div>
                <div class="control">
                    <button class="button is-primary" type="submit">Search</button>
                </div>
            </div>
        </form>

        @if($q !== '')
            <h2 class="subtitle is-4">
                {{ $pages->count() }} result{{ $pages->count() === 1 ? '' : 's' }} for “{{ $q }}”
            </h2>
        @endif

        @if($q === '')
            <div class="notification is-light">
                Type a search term above to find pages.
            </div>
        @elseif($pages->isEmpty())
            <div class="notification is-light">
                No results found for “{{ $q }}”. Try a different search term.
            </div>
        @else
            <ul class="search-results">
                @foreach($pages as $p)
                    <li class="card search-result">
                        <div class="card-content">
                            <p class="title is-4">
                                <a href="{{ url('/' . $p->slug) }}">{{ $p->title }}</a>
                            </p>
                            @if(trim((string) $p->content) !== '')
                                <p class="search-excerpt">{{ $p->content }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

@endsection
