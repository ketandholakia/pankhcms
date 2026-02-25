@extends('layouts.main')

@section('content')

<section class="section">
	<div class="container">
		@include('partials.breadcrumbs')

		<h1 class="title">{{ $page->title }}</h1>

		<div class="content">
			{!! $page->content !!}
		</div>

		{!! blocks_html('after_content') !!}
	</div>
</section>

@endsection
