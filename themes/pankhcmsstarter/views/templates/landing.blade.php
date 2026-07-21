@extends('layouts.main')

@section('no_container', '1')

@section('content')

@if(!empty($page->content))
    <section class="section">
        <div class="container">
            <div class="content">
                {!! $page->content !!}
            </div>
        </div>
    </section>
@endif

{!! render_page_blocks($blocks ?? []) !!}

{!! blocks_html('after_content') !!}

@endsection
