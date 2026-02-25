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
