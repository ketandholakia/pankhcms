@extends('layouts.main')

@section('content')
<div class="row">
    <div class="col-md-6">
        @if($page->featured_image)
            <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" class="img-fluid mb-3">
        @endif
    </div>
    <div class="col-md-6">
        <h1>{{ $page->title }}</h1>
        <div class="mb-3">
            {!! $page->content !!}
        </div>
    </div>
</div>
@endsection
