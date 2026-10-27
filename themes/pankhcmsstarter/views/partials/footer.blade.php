@php
    $siteTitle = setting('site_title', 'PankhCMS');
    $siteTagline = setting('site_tagline', 'Modern CMS for fast websites');
    $siteUrl = rtrim((string) seo_site_url(), '/');
    $footerMenu = (string) menu('footer');
    $footerLinksMenu = (string) menu('footer-links');
    $showCredit = setting('show_theme_credit', '1') === '1';
    $theme = \App\Core\Theme::active();
    // Theme::meta() reads theme.json once and caches it statically.
    $themeMeta = \App\Core\Theme::meta($theme);
    $themeAuthor = $themeMeta['author'] ?? null;
    $themeAuthorUrl = $themeMeta['author_url'] ?? null;
    $isAdmin = \App\Core\Auth::check();
@endphp

<footer class="site-footer footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>{{ $siteTitle }}</h4>
                <p>{{ $siteTagline }}</p>
                @if($siteUrl !== '')
                    <p><a href="{{ $siteUrl }}">{{ $siteUrl }}</a></p>
                @else
                    <p><a href="{{ url('/') }}">{{ url('/') }}</a></p>
                @endif
            </div>

            <div class="footer-col">
                <h4>Navigation</h4>
                @if(trim($footerMenu) !== '')
                    {!! $footerMenu !!}
                @elseif($isAdmin)
                    <p>Add a menu with location <strong>footer</strong>.</p>
                @endif
            </div>

            <div class="footer-col">
                <h4>Resources</h4>
                @if(trim($footerLinksMenu) !== '')
                    {!! $footerLinksMenu !!}
                @elseif($isAdmin)
                    <p>Add a menu with location <strong>footer-links</strong>.</p>
                @endif
            </div>

            <div class="footer-col">
                <h4>Footer Blocks</h4>
                {!! blocks_html('footer') !!}
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ $siteTitle }}{{ \App\Core\Theme::option('footer_text') ? ' — ' . \App\Core\Theme::option('footer_text') : '' }}</p>
            @if($showCredit && $themeAuthor)
                <p>
                    Theme created by
                    @if($themeAuthorUrl)
                        <a href="{{ $themeAuthorUrl }}" target="_blank" rel="noopener noreferrer">{{ $themeAuthor }}</a>
                    @else
                        {{ $themeAuthor }}
                    @endif
                </p>
            @endif
        </div>
    </div>
</footer>
