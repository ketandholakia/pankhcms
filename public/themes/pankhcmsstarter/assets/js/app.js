document.addEventListener('DOMContentLoaded', function () {
    var burgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

    burgers.forEach(function (burger) {
        var targetId = burger.dataset.target;
        var target = targetId ? document.getElementById(targetId) : null;

        var syncState = function (isOpen) {
            burger.classList.toggle('is-active', isOpen);
            burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (target) {
                target.classList.toggle('is-active', isOpen);
            }
        };

        burger.addEventListener('click', function () {
            syncState(!burger.classList.contains('is-active'));
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                syncState(false);
            }
        });

        document.addEventListener('click', function (event) {
            if (!target) {
                return;
            }

            if (!burger.contains(event.target) && !target.contains(event.target)) {
                syncState(false);
            }
        });
    });

    var tryInitCarousel = function (attemptsLeft, delay) {
        if (typeof bulmaCarousel !== 'undefined' && bulmaCarousel && typeof bulmaCarousel.attach === 'function') {
            try {
                bulmaCarousel.attach('.carousel', {
                    slidesToScroll: 1,
                    slidesToShow: 1,
                    infinite: true,
                    autoplay: true,
                    pauseOnHover: true
                });
            } catch (error) {
                // Ignore attach failures and leave the page usable without JS carousel behavior.
            }
            return;
        }

        if (attemptsLeft > 0) {
            setTimeout(function () {
                tryInitCarousel(attemptsLeft - 1, delay);
            }, delay);
        }
    };

    tryInitCarousel(8, 200);
});
