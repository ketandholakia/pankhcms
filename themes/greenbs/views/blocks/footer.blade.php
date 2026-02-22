    <!-- Footer Start -->
    <div class="container-fluid bg-footer bg-primary text-white mt-5">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div class="col-lg-4 col-md-12 pt-5 mb-5">
                            <h4 class="text-white mb-4">Get In Touch</h4>
                            <div class="d-flex mb-2">
                                <i class="bi bi-geo-alt text-white me-2"></i>
                                <p class="text-white mb-0">123 Street, New York, USA</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-envelope-open text-white me-2"></i>
                                <p class="text-white mb-0">info@example.com</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-telephone text-white me-2"></i>
                                <p class="text-white mb-0">+012 345 67890</p>
                            </div>
                            <div class="d-flex mt-4">
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="#"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="#"><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a class="btn btn-secondary btn-square rounded-circle" href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Quick Links</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Home</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>About Us</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Our Services</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Meet The Team</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Latest Blog</a>
                                <a class="text-white" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Contact Us</a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Popular Links</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Home</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>About Us</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Our Services</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Meet The Team</a>
                                <a class="text-white mb-2" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Latest Blog</a>
                                <a class="text-white" href="#"><i class="bi bi-arrow-right text-white me-2"></i>Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mt-lg-n5">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-secondary p-5">
                        <h4 class="text-white mb-4">Our Products</h4>
                        <div id="footerProductCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="https://via.placeholder.com/400x300?text=Food+Supplements" class="d-block w-100 rounded shadow" alt="Food Supplements">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5>Food Supplements</h5>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="https://via.placeholder.com/400x300?text=Herbal+Oil+Drops" class="d-block w-100 rounded shadow" alt="Herbal Oil Drops">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5>Herbal Oil Drops</h5>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="https://via.placeholder.com/400x300?text=Organic+Vegetables" class="d-block w-100 rounded shadow" alt="Organic Vegetables">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5>Organic Vegetables</h5>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="https://via.placeholder.com/400x300?text=Herbal+Powders" class="d-block w-100 rounded shadow" alt="Herbal Powders">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5>Herbal Powders</h5>
                                    </div>
                                </div>
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