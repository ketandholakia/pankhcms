{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@php
  $base = rtrim((string) seo_setting('site_url', 'canonical_base', env('APP_URL', '')), '/');
  $urls = [];

  foreach (($pages ?? []) as $page) {
      $urls[] = [
          'loc' => canonical_url($page),
          'lastmod' => $page->updated_at ?? $page->created_at ?? null,
      ];
  }

  foreach (($posts ?? []) as $post) {
      $urls[] = [
          'loc' => canonical_url($post),
          'lastmod' => $post->updated_at ?? $post->created_at ?? null,
      ];
  }

  foreach (($products ?? []) as $product) {
      $urls[] = [
          'loc' => canonical_url($product),
          'lastmod' => $product->updated_at ?? $product->created_at ?? null,
      ];
  }

  // Category/tag archive URLs are intentionally NOT emitted: there are
  // no /category/@slug or /tag/@slug public routes, so those URLs would
  // return 404 to crawlers.
@endphp
@foreach($urls as $entry)
  @if(!empty($entry['loc']))
  <url>
    <loc>{{ $entry['loc'] }}</loc>
    @if(!empty($entry['lastmod']))
    <lastmod>{{ \Carbon\Carbon::parse($entry['lastmod'])->toAtomString() }}</lastmod>
    @endif
  </url>
  @endif
@endforeach
</urlset>
