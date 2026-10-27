{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ setting('site_title', setting('site_name', 'PankhCMS')) }}</title>
        <link>{{ url('/') }}</link>
        <description>{{ setting('site_tagline', '') }}</description>
        <language>{{ setting('site_language', 'en') }}</language>
        <lastBuildDate>{{ \Illuminate\Support\Carbon::now()->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ url('/feed.xml') }}" rel="self" type="application/rss+xml" />
        @foreach($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ url('/' . $post->slug) }}</link>
                <guid isPermaLink="true">{{ url('/' . $post->slug) }}</guid>
                @if($post->published_at)
                    <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
                @endif
                <description><![CDATA[{!! \Illuminate\Support\Str::limit(trim(strip_tags((string) $post->content)), 500) !!}]]></description>
            </item>
        @endforeach
    </channel>
</rss>
