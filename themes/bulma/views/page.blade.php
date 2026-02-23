@extends('layouts.site')

@section('content')
    @include(theme_view('blocks.hero'), ['page' => $page])

    <section class="section py-5">
        <div class="container">
            <div class="columns is-variable is-6">
                <div class="column is-two-thirds">
                    @if(!empty($blocks))
                        @foreach($blocks as $block)
                            @includeIf(theme_view('blocks.' . ($block['type'] ?? '')), ['block' => $block])
                        @endforeach
                    @else
                        <div class="box">
                            <p>No blocks yet for this page.</p>
                        </div>
                    @endif
                </div>
                <div class="column is-one-third">
                    @include(theme_view('blocks.sidebar'))
                </div>
            </div>
        </div>
    </section>

    @include(theme_view('blocks.cta'))
@endsection
