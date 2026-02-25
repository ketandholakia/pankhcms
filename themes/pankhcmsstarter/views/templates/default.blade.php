@extends('layouts.main')

@section('content')

<section class="section">
	<div class="container">
		<h1 class="title">{{ $page->title }}</h1>

		<div class="content">
			{!! $page->content !!}
		</div>
	</div>
</section>

@endsection
