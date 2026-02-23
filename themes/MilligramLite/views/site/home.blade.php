@extends('layouts.main')

@section('content')

@include('blocks.hero_basic', [
	'title' => ($site_name ?? 'Site Name'),
	'subtitle' => 'Welcome to our website'
])

@include('blocks.text_content', [
	'content' => '<p>This is the homepage.</p>'
])

@endsection
