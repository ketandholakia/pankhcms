<nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm py-3 py-lg-0 px-3 px-lg-5">

<a href="/" class="navbar-brand d-flex d-lg-none">
<h1 class="m-0 display-4 text-secondary">
<span class="text-white">{{ setting('site_name') }}</span>
</h1>
</a>

<button class="navbar-toggler" data-bs-toggle="collapse"
data-bs-target="#navbarCollapse">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navbarCollapse">

<div class="navbar-nav mx-auto py-0">
{!! render_menu('header') !!}
</div>

</div>
</nav>
