/* PankhCMS starter theme — self-hosted, no external dependencies. */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- dark mode (manual toggle + system default) -------- */
    var MODE_KEY = 'pankhcms-theme-mode';

    function applyMode(mode) {
        document.documentElement.classList.toggle('theme-dark', mode === 'dark');
        document.documentElement.classList.toggle('theme-light', mode === 'light');
        var toggle = document.getElementById('theme-mode-toggle');
        if (toggle) {
            toggle.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
        }
    }

    function storedMode() {
        try { return localStorage.getItem(MODE_KEY); } catch (e) { return null; }
    }

    var stored = storedMode();
    if (stored === 'dark' || stored === 'light') {
        applyMode(stored);
    }

    /* ---------------- burger menu -------------------------------------- */
    function closeBurgers() {
        document.querySelectorAll('.navbar-burger.is-active').forEach(function (b) {
            b.classList.remove('is-active');
            b.setAttribute('aria-expanded', 'false');
            var t = b.dataset.target ? document.getElementById(b.dataset.target) : null;
            if (t) { t.classList.remove('is-active'); }
        });
    }

    function initBurgers() {
        Array.prototype.forEach.call(document.querySelectorAll('.navbar-burger'), function (burger) {
            var target = burger.dataset.target ? document.getElementById(burger.dataset.target) : null;

            burger.addEventListener('click', function () {
                var open = !burger.classList.contains('is-active');
                burger.classList.toggle('is-active', open);
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (target) { target.classList.toggle('is-active', open); }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { closeBurgers(); }
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('.navbar-burger') || event.target.closest('.navbar-menu')) {
                return;
            }
            closeBurgers();
        });
    }

    /* ---------------- dropdown menus (keyboard + touch, not hover-only) -- */
    function initDropdowns() {
        var dropdowns = Array.prototype.slice.call(document.querySelectorAll('.navbar-item.has-dropdown'));

        function closeAll(except) {
            dropdowns.forEach(function (dd) {
                if (dd === except) { return; }
                dd.classList.remove('is-active');
                var link = dd.querySelector('.navbar-link');
                if (link) { link.setAttribute('aria-expanded', 'false'); }
            });
        }

        dropdowns.forEach(function (dd) {
            var link = dd.querySelector('.navbar-link');
            if (!link) { return; }
            link.setAttribute('aria-expanded', 'false');
            link.setAttribute('aria-haspopup', 'true');

            var toggle = function () {
                var open = dd.classList.toggle('is-active');
                link.setAttribute('aria-expanded', open ? 'true' : 'false');
                closeAll(dd);
            };

            link.addEventListener('click', function (event) {
                event.preventDefault();
                toggle();
            });
            link.addEventListener('keydown', function (event) {
                if (event.key === ' ' || event.key === 'Enter') {
                    event.preventDefault();
                    toggle();
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { closeAll(null); }
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.navbar-item.has-dropdown')) { closeAll(null); }
        });
    }

    /* ---------------- scroll-snap slider -------------------------------- */
    function initSliders() {
        Array.prototype.forEach.call(document.querySelectorAll('.carousel'), function (carousel) {
            var items = carousel.querySelectorAll('.item');
            if (items.length === 0) { return; }

            var index = 0;
            var autoplay = carousel.dataset.autoplay === 'true' && !reduceMotion && items.length > 1;
            var delay = parseInt(carousel.dataset.delay || '5000', 10);
            var timer = null;

            var nav = document.createElement('div');
            nav.className = 'carousel-navigation';

            var prev = document.createElement('button');
            prev.type = 'button';
            prev.className = 'carousel-btn carousel-prev';
            prev.setAttribute('aria-label', 'Previous slide');
            prev.innerHTML = '&lsaquo;';

            var next = document.createElement('button');
            next.type = 'button';
            next.className = 'carousel-btn carousel-next';
            next.setAttribute('aria-label', 'Next slide');
            next.innerHTML = '&rsaquo;';

            var dots = document.createElement('div');
            dots.className = 'carousel-dots';
            var dotButtons = [];
            Array.prototype.forEach.call(items, function (_, i) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'carousel-dot';
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', function () { goTo(i); });
                dots.appendChild(dot);
                dotButtons.push(dot);
            });

            var pause = null;
            if (autoplay) {
                pause = document.createElement('button');
                pause.type = 'button';
                pause.className = 'carousel-btn carousel-pause';
                pause.setAttribute('aria-label', 'Pause slideshow');
                pause.textContent = 'II';
            }

            nav.appendChild(prev);
            nav.appendChild(dots);
            if (pause) { nav.appendChild(pause); }
            nav.appendChild(next);
            carousel.appendChild(nav);

            function currentFromScroll() {
                var best = 0;
                var bestDist = Infinity;
                Array.prototype.forEach.call(items, function (item, i) {
                    var dist = Math.abs(item.offsetLeft - carousel.scrollLeft);
                    if (dist < bestDist) { bestDist = dist; best = i; }
                });
                return best;
            }

            function goTo(i) {
                index = (i + items.length) % items.length;
                carousel.scrollTo({ left: items[index].offsetLeft, behavior: reduceMotion ? 'auto' : 'smooth' });
                syncDots();
            }

            function syncDots() {
                var current = currentFromScroll();
                dotButtons.forEach(function (dot, i) {
                    dot.classList.toggle('is-active', i === current);
                });
            }

            prev.addEventListener('click', function () { goTo(index - 1); stopAutoplay(); });
            next.addEventListener('click', function () { goTo(index + 1); stopAutoplay(); });

            function startAutoplay() {
                if (!autoplay || timer) { return; }
                timer = setInterval(function () { goTo(index + 1); }, delay);
            }
            function stopAutoplay() {
                if (timer) { clearInterval(timer); timer = null; }
                if (pause) { pause.textContent = 'Play'; }
            }

            if (pause) {
                pause.addEventListener('click', function () {
                    if (timer) { stopAutoplay(); } else { pause.textContent = 'Pause'; startAutoplay(); }
                });
                carousel.addEventListener('mouseenter', stopAutoplay);
                startAutoplay();
            }

            carousel.addEventListener('scroll', function () { syncDots(); }, { passive: true });
            syncDots();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggleButton = document.getElementById('theme-mode-toggle');
        if (toggleButton) {
            applyMode(storedMode() || 'light');
            toggleButton.addEventListener('click', function () {
                var next = document.documentElement.classList.contains('theme-dark') ? 'light' : 'dark';
                applyMode(next);
                try { localStorage.setItem(MODE_KEY, next); } catch (e) { /* storage unavailable */ }
            });
        } else {
            applyMode(storedMode() || 'light');
        }

        initBurgers();
        initDropdowns();
        initSliders();
    });
})();
