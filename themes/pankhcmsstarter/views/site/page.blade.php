@extends('layouts.main')

@section('content')

<section class="section">
	<div class="container">
		@include('partials.breadcrumbs')

		<h1 class="title">{{ $page->title }}</h1>

		@if(!empty($page->content))
			<div class="content">
				{!! $page->content !!}
			</div>
		@endif

		{!! render_page_blocks($blocks ?? []) !!}

		{!! blocks_html('after_content') !!}
	</div>
</section>

@endsection
