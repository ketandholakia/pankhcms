@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i data-lucide="image"></i>
            Select Media
        </h1>
        <p class="text-sm text-gray-500">Choose a file to insert</p>
    </div>

    @if($media->isEmpty())
        <div class="bg-white border rounded shadow p-8 text-center text-gray-500">
            No media available.
        </div>
    @else
        {{-- Markup mirrors the AJAX grid emitted by MediaController::picker() --}}
        <div class="row">
            @foreach($media as $item)
                <div class="col-6 col-md-3 col-lg-2 mb-3">
                    <div class="card">
                        @if(str_starts_with((string) $item->mime_type, 'image/'))
                            <img src="{{ $item->url }}" class="card-img-top" style="height:120px;object-fit:cover" alt="">
                        @else
                            <div class="card-body text-center"><span class="text-muted">{{ $item->mime_type }}</span></div>
                        @endif
                        <div class="card-body p-2 text-center">
                            <button type="button" class="btn btn-sm btn-success media-picker-select-btn" data-id="{{ (int) $item->id }}" data-url="{{ $item->url }}">Select</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if(!empty($paginator) && ($paginator['last'] ?? 0) > 1)
    @php
        $pagerParams = $_GET ?? [];
        unset($pagerParams['page']);
    @endphp
    <div class="flex items-center justify-between mt-6">
        <div>
            @if($paginator['current'] > 1)
                @php $prev = array_merge($pagerParams, ['page' => $paginator['current'] - 1]); @endphp
                <a href="?{{ http_build_query($prev) }}" class="px-3 py-2 bg-gray-100 rounded">&laquo; Previous</a>
            @endif
        </div>
        <div class="text-sm text-gray-600">Page {{ $paginator['current'] }} of {{ $paginator['last'] }} ({{ $paginator['total'] }} items)</div>
        <div>
            @if($paginator['current'] < $paginator['last'])
                @php $next = array_merge($pagerParams, ['page' => $paginator['current'] + 1]); @endphp
                <a href="?{{ http_build_query($next) }}" class="px-3 py-2 bg-gray-100 rounded">Next &raquo;</a>
            @endif
        </div>
    </div>
@endif
@endsection
