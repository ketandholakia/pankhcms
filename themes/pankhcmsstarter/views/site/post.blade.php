@extends('layouts.main')

@section('content')

<article>
    <h1>{{ $post->title }}</h1>
    {!! $post->content !!}
</article>

@endsection
