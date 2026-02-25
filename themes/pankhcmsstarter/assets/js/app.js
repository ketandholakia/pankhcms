// PankhCMS Starter Theme JS (Bulma navbar burger)
document.addEventListener('DOMContentLoaded', function () {
	var burgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
	if (!burgers.length) return;

	burgers.forEach(function (burger) {
		burger.addEventListener('click', function () {
			var targetId = burger.dataset.target;
			var target = document.getElementById(targetId);
			burger.classList.toggle('is-active');
			if (target) target.classList.toggle('is-active');
		});
	});
});

// Initialize Bulma carousel with retry in case the library loads slightly later
document.addEventListener('DOMContentLoaded', () => {
	const tryInit = (attemptsLeft = 8, delay = 200) => {
		if (typeof bulmaCarousel !== 'undefined' && bulmaCarousel && typeof bulmaCarousel.attach === 'function') {
			try {
				bulmaCarousel.attach('.carousel', {
					slidesToScroll: 1,
					slidesToShow: 1,
					infinite: true,
					autoplay: true,
					pauseOnHover: true
				});
			} catch (e) {
				// silently ignore init errors
				// console.error('bulmaCarousel init error', e);
			}
			return;
		}

		if (attemptsLeft > 0) {
			setTimeout(() => tryInit(attemptsLeft - 1, delay), delay);
		}
	};

	tryInit();
});
