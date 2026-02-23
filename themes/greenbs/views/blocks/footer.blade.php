    <!-- Footer Start -->
    <div class="container-fluid bg-footer bg-primary text-white mt-5">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div class="col-lg-5 col-md-12 pt-5 mb-5">
                            <h4 class="text-white mb-4">Get In Touch</h4>
                                <div class="d-flex mb-2">
                                    <i class="bi bi-geo-alt text-white me-2"></i>
                                    <p class="text-white mb-0">ORGANIC DEHYDRATED FOODS PVT LTD<br>
                                    Plot No. D-38, Kamdhenu Industrial Park - 5,<br>
                                    B/H. BEIL Company, Jitali Road,<br>
                                    Ankleshwar - 393 001, Gujarat, India</p>
                                </div>
                                
                            
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Contacts:</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <div class="d-flex mb-2">
                                    <i class="bi bi-envelope-open text-white me-2"></i>
                                    <p class="text-white mb-0">organic.de.foods@gmail.com</p>
                                </div>
                                <div class="d-flex mb-2">
                                    <i class="bi bi-telephone text-white me-2"></i>
                                    <p class="text-white mb-0">+91 70453 55128<br>+91 98799 29290<br>+91 95861 46660</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Popular Links</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Home</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>About Us</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Company Profile</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Process</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Certifications</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Products</a>
                                <a class="text-white" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Inquiry</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mt-lg-n5">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-secondary p-3">
                        <h4 class="text-white mb-4">Our Products</h4>
                        @php
                            $footerProducts = \App\Models\Page::where('type', 'products')
                                ->whereNotNull('featured_image')
                                ->where('featured_image', '!=', '')
                                ->where('status', 'published')
                                ->select('title', 'featured_image', 'slug')
                                ->get();
                        @endphp
                        <div id="footerProductCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @forelse($footerProducts as $i => $product)
                                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                                    <a href="/{{ $product->slug }}">
                                        <img src="{{ $product->featured_image }}" class="d-block w-100 rounded shadow" alt="{{ $product->title }}" style="height:280px;object-fit:cover;">
                                    </a>
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5>{{ $product->title }}</h5>
                                    </div>
                                </div>
                                @empty
                                <div class="carousel-item active">
                                    <p class="text-white text-center pt-4">No products found.</p>
                                </div>
                                @endforelse
                            </div>
                            @if($footerProducts->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#footerProductCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#footerProductCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="{{ isset($domain) ? $domain : setting('site_url', '/') }}">{{ setting('site_name', 'Your Site Name') }}</a>. All Rights Reserved.
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
    <!-- Footer End -->