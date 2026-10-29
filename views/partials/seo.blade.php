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

@php($isPost = $currentPage !== null && ($currentPage->type ?? 'page') === 'post')
<meta property="og:type" content="{{ $isPost ? 'article' : 'website' }}">
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

@php($siteUrl = rtrim((string) seo_setting('site_url', 'canonical_base', env('APP_URL', '')), '/'))
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => seo_site_title(),
    'url' => $siteUrl !== '' ? $siteUrl . '/' : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => seo_site_title(),
    'url' => $siteUrl !== '' ? $siteUrl . '/' : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@if($isPost && !empty($currentPage))
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => seo_title($currentPage),
    'description' => seo_description($currentPage),
    'url' => canonical_url($currentPage),
    'image' => !empty($currentPage->featured_image) ? seo_absolute_url($currentPage->featured_image) : null,
    'datePublished' => !empty($currentPage->published_at) ? $currentPage->published_at->toIso8601String() : null,
    'dateModified' => !empty($currentPage->updated_at) ? $currentPage->updated_at->toIso8601String() : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif