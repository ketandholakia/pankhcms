@extends('layouts.main')

@section('content')
@php
    $productDetail = $page->customField('productdetail', '');
@endphp

<div class="container py-4">

    <div class="row g-4">

        {{-- LEFT: Product Image --}}
        <div class="col-lg-6">

            <div class="card border-0 shadow-sm">

                <div class="ratio ratio-1x1 bg-light">
                    @if($page->featured_image)
                        <img src="{{ $page->featured_image }}"
                             alt="{{ $page->title }}"
                             class="img-fluid object-fit-cover p-3">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-muted">
                            No Image Available
                        </div>
                    @endif
                </div>

            </div>

        </div>


        {{-- RIGHT: Product Info --}}
        <div class="col-lg-6">

            <div class="h-100 d-flex flex-column">

                {{-- Title --}}
                <h1 class="fw-bold mb-2">{{ $page->title }}</h1>

                {{-- Category / Badge (Optional) --}}
                @if($page->category ?? false)
                    <span class="badge bg-success mb-3 align-self-start">
                        {{ $page->category->name }}
                    </span>
                @endif


                {{-- Product Description --}}
                <div class="mb-4 text-muted fs-6">
                    {!! $page->content ?: e($productDetail) !!}
                </div>


                {{-- Features Box --}}
                <div class="card border-0 bg-light mb-4">
                    <div class="card-body">

                        <h5 class="fw-semibold mb-3">Product Highlights</h5>

                        <ul class="list-unstyled mb-0">

                            <li class="mb-2">
                                ✔ 100% Natural & Organic
                            </li>

                            <li class="mb-2">
                                ✔ No Artificial Additives
                            </li>

                            <li class="mb-2">
                                ✔ Hygienically Processed
                            </li>

                            <li>
                                ✔ Suitable for Daily Use
                            </li>

                        </ul>

                    </div>
                </div>


                {{-- Action Buttons --}}
                <div class="mt-auto d-flex gap-2">

                    <a href="/contact"
                       class="btn btn-success btn-lg px-4">
                        Enquire Now
                    </a>

                    <a href="/products"
                       class="btn btn-outline-secondary btn-lg px-4">
                        Back to Products
                    </a>

                </div>

            </div>

        </div>

    </div>


    {{-- OPTIONAL: Additional Info Section --}}
    <div class="row mt-5">

        <div class="col-12">

            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <h3 class="fw-semibold mb-3">
                        Product Details
                    </h3>

                    <div class="text-muted">
                        {!! $productDetail !!}
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>
@endsection