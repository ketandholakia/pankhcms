@extends('layouts.site')

@section('hero')
    @include('partials.slider')
@endsection

@section('content')
    @if(!empty($blocks))
        @foreach($blocks as $block)
            @includeIf('site.blocks.' . $block['type'], ['block' => $block])
        @endforeach
    @elseif(isset($site_name))
        <div class="text-center py-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Welcome to {{ $site_name }}</h1>
            <p class="text-gray-500">Your home page content will appear here.</p>
        </div>
    @endif
@endsection
