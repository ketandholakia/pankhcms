<header class="site-header">

    {!! blocks_html('header_top') !!}

    @php
        $menuHtml = (string) menu('main');
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
                @endphp
                @if($logoEnabled == '1' && $logoPath)
                    <img src="{{ $logoPath }}" alt="{{ $siteTitle }}" style="max-height: 48px;">
                @else
                    <span class="logo-text">{{ $siteTitle }}</span>
                @endif
            </a>

            <a role="button" class="navbar-burger" aria-label="menu"
               aria-expanded="false" data-target="mainNavbar">
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
                    <a href="/contact-us" class="button is-primary is-rounded cta-button">
                        Get Quote
                    </a>
                </div>

            </div>
        </div>

    </div>
</nav>  

    {!! blocks_html('header_bottom') !!}

</header>
