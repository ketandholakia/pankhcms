@extends('layouts.site')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">
        Search results for "{{ $q }}"
    </h1>

    @if($q === '')
        <p>Please enter a search term.</p>
    @elseif($pages->isEmpty())
        <p>No results found.</p>
    @else
        <ul class="space-y-4">
            @foreach($pages as $p)
                <li>
                    <a href="/{{ $p->slug }}" class="text-blue-600 text-lg font-semibold">
                        {{ $p->title }}
                    </a>

                    <p class="text-gray-600">
                        {{ \Illuminate\Support\Str::limit(strip_tags($p->content ?? ''), 150) }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
