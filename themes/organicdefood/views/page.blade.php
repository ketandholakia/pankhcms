@extends('layouts.main')

@section('content')
    @include('blocks.hero', ['page' => $page])

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    @if(!empty($blocks))
                        @foreach($blocks as $block)
                            @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
                        @endforeach
                    @else
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <p class="mb-0">No blocks yet for this page.</p>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-lg-4">
                    @include('blocks.sidebar')
                </div>
            </div>
        </div>
    </section>

    @include('blocks.cta')
@endsection
