@extends('layouts.site')

@section('content')
    @include('blocks.hero', ['page' => $page])

    <section class="section py-5">
        <div class="container">
            <div class="columns is-variable is-6">
                <div class="column is-two-thirds">
                    @if(!empty($blocks))
                        @foreach($blocks as $block)
                            @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
                        @endforeach
                    @else
                        <div class="box">
                            <p>No blocks yet for this page.</p>
                        </div>
                    @endif
                </div>
                <div class="column is-one-third">
                    @include('blocks.sidebar')
                </div>
            </div>
        </div>
    </section>

    @include('blocks.cta')
@endsection
