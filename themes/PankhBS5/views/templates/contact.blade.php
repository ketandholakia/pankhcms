@extends('layouts.boxed')

@section('boxed_content')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">{{ $page->title ?? 'Contact Us' }}</h1>
                    @if(!empty($page->content))
                        <div class="content-block mb-3">{!! $page->content !!}</div>
                    @endif
                    @if(isset($blocks) && is_array($blocks))
                        @foreach($blocks as $block)
                            @includeIf('blocks.' . ($block['type'] ?? ''), ['block' => $block])
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @includeIf(theme_view('blocks.contact_info'), ['block' => []])
        </div>
    </div>
@endsection
