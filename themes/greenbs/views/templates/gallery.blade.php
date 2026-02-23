@extends('layouts.main')

@section('content')
<div class="container py-4">
    <h1 class="fw-bold mb-4">Product Gallery</h1>
    @includeIf(theme_view('blocks.product_gallery'))
</div>
@endsection
