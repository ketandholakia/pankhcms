@extends('layouts.main')

@section('content')

<h1>Blog</h1>

@foreach($posts as $post)
    <article>
        <h2>{{ $post->title }}</h2>
        <p>{{ $post->excerpt }}</p>
    </article>
@endforeach

@endsection
