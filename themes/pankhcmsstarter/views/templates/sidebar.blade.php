@extends('layouts.main')

@section('content')

<section class="section">
    <div class="container">
        <div class="columns">
            <div class="column is-8">
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
            <div class="column is-4">
                @include('partials.sidebar')
            </div>
        </div>
    </div>
</section>

@endsection
