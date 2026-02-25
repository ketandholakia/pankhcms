@extends('layouts.main')

@section('content')

<section class="section">
    <div class="container">
        <div class="content">
            {!! $page->content !!}
        </div>
    </div>
</section>

@endsection
