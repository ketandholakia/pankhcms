@extends('layouts.main')

@section('content')
<div class="container py-4">
    <h1 class="fw-bold mb-4">Our Products</h1>
    @include(theme_view('site.product_gallery'))
</div>
@endsection