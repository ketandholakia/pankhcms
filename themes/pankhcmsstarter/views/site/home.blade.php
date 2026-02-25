@extends('layouts.main')

@section('content')

{!! blocks_html('homepage_top') !!}

<section class="hero is-light">
    <div class="hero-body">
        <div class="container">
            <h1 class="title">Welcome to PankhCMS</h1>
            <p class="subtitle">PankhCMS Starter Theme</p>
        </div>
    </div>
</section>

{!! blocks_html('homepage_middle') !!}

{!! blocks_html('homepage_bottom') !!}

@endsection
