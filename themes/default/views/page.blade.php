@extends('layouts.site')

@section('content')

<article class="content card">

  <h1>{{ $page['title'] }}</h1>

  {!! $page['content'] !!}

</article>

@endsection
