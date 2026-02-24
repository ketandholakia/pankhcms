@extends('layouts.main')

@section('content')
@php
    session_init();
    $captchaA = random_int(1, 9);
    $captchaB = random_int(1, 9);
    $_SESSION['contact_captcha_expected'] = (string) ($captchaA + $captchaB);
@endphp
<section class="py-5 bg-light border-top border-bottom">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <h1 class="display-5 mb-3">Contact Us</h1>
                <p class="mb-0 text-muted">Have questions or need help with our products? Send us a message and our team will get back to you shortly.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <span class="badge bg-primary fs-6 px-3 py-2">We usually reply within 24 hours</span>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-white p-4 p-md-5 shadow-sm rounded">
                    <h3 class="mb-4">Send a Message</h3>

                    <form id="contactForm" novalidate>
                        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject">
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="captcha" class="form-label">Captcha: What is {{ $captchaA }} + {{ $captchaB }}? <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="captcha" name="captcha" required>
                            </div>
                            <div class="col-12">
                                <button id="contactSubmitBtn" type="submit" class="btn btn-primary px-4 py-2">Send Message</button>
                            </div>
                        </div>
                    </form>

                    <div id="formStatus" class="mt-4" aria-live="polite"></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="bg-white p-4 shadow-sm rounded h-100">
                    <h4 class="mb-4">Contact Information</h4>

                    <div class="d-flex align-items-start mb-3">
                        <div class="btn btn-square bg-secondary text-white me-3"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <h6 class="mb-1">Office Address</h6>
                            <p class="text-muted mb-0">ORGANIC DEHYDRATED FOODS PVT LTD<br>
                            Plot No. D-38, Kamdhenu Industrial Park - 5,<br>
                            B/H. BEIL Company, Jitali Road,<br>
                            Ankleshwar - 393 001, Gujarat, India</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3">
                        <div class="btn btn-square bg-secondary text-white me-3"><i class="bi bi-envelope-open"></i></div>
                        <div>
                            <h6 class="mb-1">Email</h6>
                            <a href="mailto:organic.de.foods@gmail.com" class="text-muted">organic.de.foods@gmail.com</a>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="btn btn-square bg-secondary text-white me-3"><i class="bi bi-telephone"></i></div>
                        <div>
                            <h6 class="mb-1">Phone</h6>
                            <a href="tel:+917045355128" class="text-muted">+91 70453 55128</a><br>
                            <a href="tel:+919879929290" class="text-muted">+91 98799 29290</a><br>
                            <a href="tel:+919586146660" class="text-muted">+91 95861 46660</a>
                        </div>
                    </div>

                   
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-0">
    @php
        $rawMapValue = trim((string) ($contact_map_embed_url ?? ''));
        $defaultQuery = 'New York, USA';

        $mapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($defaultQuery) . '&output=embed';
        $mapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($defaultQuery);

        if ($rawMapValue !== '') {
            $mapOpenUrl = $rawMapValue;

            if (str_contains($rawMapValue, '/maps/embed?') || str_contains($rawMapValue, 'output=embed')) {
                $mapEmbedUrl = $rawMapValue;
            } elseif (filter_var($rawMapValue, FILTER_VALIDATE_URL)) {
                $parts = parse_url($rawMapValue);
                $query = '';

                if (!empty($parts['query'])) {
                    parse_str($parts['query'], $queryParams);
                    $query = trim((string) ($queryParams['q'] ?? $queryParams['query'] ?? ''));
                }

                if ($query === '' && !empty($parts['path']) && str_contains($parts['path'], '/maps/place/')) {
                    $placePart = explode('/maps/place/', $parts['path'], 2)[1] ?? '';
                    $placePart = explode('/', $placePart, 2)[0] ?? $placePart;
                    $query = trim(str_replace('+', ' ', urldecode($placePart)));
                }

                if ($query !== '') {
                    $mapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($query) . '&output=embed';
                } else {
                    $mapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($rawMapValue) . '&output=embed';
                }
            } else {
                $mapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($rawMapValue) . '&output=embed';
                $mapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($rawMapValue);
            }
        }
    @endphp
    <div class="container-fluid px-0">
        <iframe
            src="{{ $mapEmbedUrl }}"
            width="100%"
            height="450"
            style="border:0; display:block;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Google Map Location"></iframe>
    </div>
    <div class="container text-center py-3">
        <a href="{{ $mapOpenUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary px-4 py-2">
            Open in Google Maps
        </a>
    </div>
</section>

<script>
(function () {
    const form = document.getElementById('contactForm');
    const status = document.getElementById('formStatus');
    const submitBtn = document.getElementById('contactSubmitBtn');

    if (!form || !status || !submitBtn) {
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        status.innerHTML = '';

        if (!form.checkValidity()) {
            status.innerHTML = '<div class="alert alert-danger mb-0">Please fill all required fields.</div>';
            return;
        }

        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';

        try {
            const response = await fetch('/contact', {
                method: 'POST',
                body: new FormData(form)
            });

            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                data = {};
            }

            if (response.ok && data.success) {
                status.innerHTML = '<div class="alert alert-success mb-0">Message sent successfully. We will get back to you soon.</div>';
                form.reset();
            } else {
                status.innerHTML = '<div class="alert alert-danger mb-0">' + (data.error || 'Unable to send your message right now. Please try again.') + '</div>';
            }
        } catch (error) {
            status.innerHTML = '<div class="alert alert-danger mb-0">Network error. Please try again.</div>';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
})();
</script>
@endsection
