@extends('layouts.main')

@section('content')

<section class="section">
    <div class="container">
        <div class="columns">
            <div class="column is-8">
                <div class="content">
                    {!! $page->content !!}
                </div>
            </div>
            <div class="column is-4">
                @include('partials.sidebar')
            </div>
        </div>
    </div>
</section>

@endsection
