<!-- Footer Start -->
    @php
        // Example: fetch from settings or config, or pass from controller
        $footer_address = setting('footer_address', 'Company Name<br>Address line 1<br>City, State, ZIP, Country');
        $footer_email = setting('footer_email', 'info@example.com');
        $footer_phones = explode(',', setting('footer_phones', '+91 12345 67890'));
        $footer_links = json_decode(setting('footer_links', '[{"label":"Home","url":"/"},{"label":"About Us","url":"/about"},{"label":"Products","url":"/products"},{"label":"Contact","url":"/contact"}]'), true);
        $footer_products = json_decode(setting('footer_products', '[{"title":"Food Supplements","image":"https://via.placeholder.com/400x300?text=Food+Supplements"},{"title":"Herbal Oil Drops","image":"https://via.placeholder.com/400x300?text=Herbal+Oil+Drops"},{"title":"Organic Vegetables","image":"https://via.placeholder.com/400x300?text=Organic+Vegetables"},{"title":"Herbal Powders","image":"https://via.placeholder.com/400x300?text=Herbal+Powders"}]'), true);
        $site_name = setting('site_name', 'Your Site Name');
        $footer_about = setting('footer_about', 'About your company or website goes here.');
        $contact_phone = setting('contact_phone', '+91 12345 67890');
    @endphp
    <div class="container-fluid bg-footer bg-primary text-white mt-5">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div class="col-lg-5 col-md-12 pt-5 mb-5">
                            <h4 class="text-white mb-4">Get In Touch</h4>
                            <div class="d-flex mb-2">
                                <i class="bi bi-geo-alt text-white me-2"></i>
                                <p class="text-white mb-0">{!! $footer_address !!}</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-info-circle text-white me-2"></i>
                                <p class="text-white mb-0">{{ $footer_about }}</p>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Contacts:</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <div class="d-flex mb-2">
                                    <i class="bi bi-envelope-open text-white me-2"></i>
                                    <p class="text-white mb-0">{{ $footer_email }}</p>
                                </div>
                                <div class="d-flex mb-2">
                                    <i class="bi bi-telephone text-white me-2"></i>
                                    <p class="text-white mb-0">
                                        @foreach($footer_phones as $phone)
                                            {{ trim($phone) }}<br>
                                        @endforeach
                                        <span>{{ $contact_phone }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Popular Links</h4>
                            <div class="d-flex flex-column justify-content-start">
                                @foreach($footer_links as $link)
                                    <a class="text-white mb-2" href="{{ $link['url'] }}"><i class="bi bi-arrow-right text-white me-2"></i>{{ $link['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mt-lg-n5">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-secondary p-5">
                        <h4 class="text-white mb-4">Our Products</h4>
                        <div id="footerProductCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach($footer_products as $i => $product)
                                    <div class="carousel-item{{ $i === 0 ? ' active' : '' }}">
                                        <img src="{{ $product['image'] }}" class="d-block w-100 rounded shadow" alt="{{ $product['title'] }}">
                                        <div class="carousel-caption d-none d-md-block">
                                            <h5>{{ $product['title'] }}</h5>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#footerProductCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#footerProductCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="{{ isset($domain) ? $domain : setting('site_url', '/') }}">{{ $site_name }}</a>. All Rights Reserved.
            @php
                $showCredit = setting('show_theme_credit', '1') === '1';
                $theme = setting('active_theme', 'greenbs');
                $projectRoot = env('PROJECT_ROOT', '/shared/httpd/pankhCMS');
                $themeJsonPath = $projectRoot . '/themes/greenbs/theme.json';
                $themeAuthor = null;
                $themeAuthorUrl = null;
                if (file_exists($themeJsonPath)) {
                    $themeMeta = json_decode(file_get_contents($themeJsonPath), true);
                    $themeAuthor = $themeMeta['author'] ?? null;
                    $themeAuthorUrl = $themeMeta['author_url'] ?? null;
                }
            @endphp
            @if($showCredit && $themeAuthor)
                Theme by <span class="text-secondary fw-bold">
                    @if($themeAuthorUrl)
                        <a href="{{ $themeAuthorUrl }}" target="_blank" rel="noopener noreferrer" class="text-secondary fw-bold text-decoration-none">{{ $themeAuthor }}</a>
                    @else
                        {{ $themeAuthor }}
                    @endif
                </span>
            @endif
            </p>
        </div>
    </div>
    {{-- Render all global blocks assigned to the 'footer' location --}}
    @foreach(blocks('footer') as $block)
        @include('blocks.' . $block->type, ['data' => $block->content])
    @endforeach
    <!-- Footer End -->