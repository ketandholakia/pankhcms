@extends('layouts.main')

@section('content')

<section class="section not-found">
    <div class="container has-text-centered">
        <h1 class="title is-1">404</h1>
        <p class="subtitle">The page you are looking for does not exist or has moved.</p>

        <form class="search-form" method="GET" action="{{ url('/search') }}" role="search">
            <div class="field has-addons">
                <div class="control is-expanded">
                    <input class="input" type="search" name="q" placeholder="Search the site…" aria-label="Search query">
                </div>
                <div class="control">
                    <button class="button is-primary" type="submit">Search</button>
                </div>
            </div>
        </form>

        @if(!empty($recent) && count($recent))
            <h2 class="title is-4 mt-5">Recent pages</h2>
            <ul class="recent-pages">
                @foreach($recent as $item)
                    <li><a href="{{ url('/' . $item->slug) }}">{{ $item->title }}</a></li>
                @endforeach
            </ul>
        @endif

        <p class="mt-5"><a class="button is-light" href="{{ url('/') }}">Back to home</a></p>
    </div>
</section>

@endsection
