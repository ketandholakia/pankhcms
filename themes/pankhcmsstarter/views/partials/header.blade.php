<header class="site-header">
    {!! blocks_html('header_top') !!}

    @php
        $menuHtml = (string) menu('topbar');
        if (trim($menuHtml) === '') {
            $menuHtml = (string) menu('main');
        }
        if (trim($menuHtml) === '') {
            $menuHtml = (string) menu('header');
        }
    @endphp

    <nav class="navbar is-white is-spaced has-shadow custom-navbar" role="navigation" aria-label="main navigation">
    <div class="container">

        <!-- BRAND -->
        <div class="navbar-brand">
            <a class="navbar-item brand-logo" href="/">
                @php
                    $logoPath = setting('logo_path', '/public/assets/tinymce/logo.png');
            $siteTitle = setting('site_title', 'PankhCMS');
            $logoEnabled = setting('logo_enabled', '1');
            $tagline = setting('site_tagline', '');
            $ctaLabel = setting('header_cta_label', 'Get Quote');
            $ctaUrl = setting('header_cta_url', '/contact-us');
        @endphp
        @if($logoEnabled == '1' && $logoPath)
            <img src="{{ $logoPath }}" alt="{{ $siteTitle }}" style="max-height: 48px;">
        @else
            <span class="logo-lockup">
                <span class="logo-text">{{ $siteTitle }}</span>
                @if($tagline !== '')
                    <span class="logo-tagline">{{ $tagline }}</span>
                @endif
            </span>
        @endif
    </a>

    <a role="button" class="navbar-burger" aria-label="menu"
       aria-expanded="false" aria-controls="mainNavbar" data-target="mainNavbar">
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
            </a>
        </div>

        <!-- MENU -->
        <div id="mainNavbar" class="navbar-menu">

            <div class="navbar-start nav-links">
                {!! $menuHtml !!}
            </div>

            <!-- RIGHT SIDE -->
            <div class="navbar-end">
                <div class="navbar-item">
                    <a href="{{ $ctaUrl }}" class="button is-primary is-rounded cta-button">
                        {{ $ctaLabel }}
                    </a>
                </div>
            </div>
        </div>

    </div>
</nav>

    {!! blocks_html('header_bottom') !!}
</header>
