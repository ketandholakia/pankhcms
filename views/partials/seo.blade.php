    @php($currentPage = $page ?? null)

<title>{{ seo_title($currentPage) }}</title>
<meta name="description" content="{{ seo_description($currentPage) }}">
<meta name="keywords" content="{{ seo_keywords($currentPage) }}">

@php($favicon = seo_setting('favicon', 'logo_path', ''))
@if($favicon)
    <link rel="icon" href="{{ seo_absolute_url($favicon) }}">
@endif

<link rel="canonical" href="{{ canonical_url($currentPage) }}">
<meta name="robots" content="{{ seo_robots($currentPage) }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ seo_title($currentPage) }}">
<meta property="og:description" content="{{ seo_description($currentPage) }}">
<meta property="og:image" content="{{ seo_image($currentPage) }}">
<meta property="og:url" content="{{ canonical_url($currentPage) }}">
<meta property="og:site_name" content="{{ seo_site_title() }}">

<meta name="twitter:card" content="{{ seo_twitter_card($currentPage) }}">
<meta name="twitter:title" content="{{ seo_title($currentPage) }}">
<meta name="twitter:description" content="{{ seo_description($currentPage) }}">
<meta name="twitter:image" content="{{ seo_image($currentPage) }}">

@if(!empty($breadcrumbs))
    {!! breadcrumbSchema($breadcrumbs) !!}
@endif