document.addEventListener('DOMContentLoaded', function () {
  var navbar = document.querySelector('.navbar-collapse');
  if (!navbar) return;

  navbar.querySelectorAll('a').forEach(function (anchor) {
    anchor.addEventListener('click', function () {
      if (window.innerWidth < 992 && navbar.classList.contains('show')) {
        var toggler = document.querySelector('.navbar-toggler');
        if (toggler) {
          toggler.click();
        }
      }
    });
  });
});
