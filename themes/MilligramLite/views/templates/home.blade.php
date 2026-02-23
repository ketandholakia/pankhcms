@extends('layouts.main')

@section('content')

@includeIf(theme_view('blocks.slider'))

@include('blocks.hero_basic', [
    'title' => ($site_name ?? 'Site Name'),
    'subtitle' => 'Welcome to our website'
])

@include('blocks.text_content', [
    'content' => '<p>This is the homepage.</p>'
])

@endsection
