@extends('layouts.main')

@section('content')

@includeIf(theme_view('blocks.slider'))

@include('blocks.hero_pro', [
  'title' => ($site_name ?? 'Site Name'),
  'subtitle' => 'High-quality products for modern needs',
  'button' => 'Explore Products',
  'button_link' => '/product'
])

@include('blocks.features_grid')

@include('blocks.product_grid')

@include('blocks.cta_box', [
  'title' => 'Need Bulk Orders?',
  'text' => 'Contact us for wholesale pricing.',
  'button' => 'Contact Us',
  'link' => '/contact'
])

@endsection
