<nav class="navbar navbar-expand-lg bg-secondary text-uppercase fixed-top">
    <div class="container">

        <a class="navbar-brand" href="/">
            {{ $site_name ?? 'PankhCMS' }}
        </a>

        <button class="navbar-toggler text-uppercase font-weight-bold bg-primary text-white rounded"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarResponsive">
            Menu <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarResponsive">

            @include('partials.menu', [
                'items' => $menuItems ?? []
            ])

        </div>

    </div>
</nav>
