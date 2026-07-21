@extends('layouts.site')

@section('content')
    {!! render_page_blocks($blocks ?? []) !!}
@endsection
