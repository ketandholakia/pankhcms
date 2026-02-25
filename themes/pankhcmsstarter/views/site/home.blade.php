@extends('layouts.main')

@section('content')

{!! blocks_html('homepage_top') !!}


<section class="hero-slider">

<div class="carousel" data-autoplay="true" data-delay="5000">

    <!-- Slide 1 -->
    <div class="item">
        <section class="hero is-medium hero-slide slide-1">
            <div class="hero-body">
                <div class="container">

                    <div class="columns is-vcentered">

                        <div class="column is-6">
                            <h1 class="title is-1">
                                {{ setting('site_name') }}
                            </h1>

                            <h2 class="subtitle is-4">
                                {{ setting('site_tagline') }}
                            </h2>

                            <p>
                                Transforming ideas into powerful digital solutions
                                with cutting-edge technology.
                            </p>

                            <br>

                            <a href="/contact-us" class="button is-link is-medium">
                                Get Started
                            </a>

                            <a href="/projects" class="button is-light is-medium">
                                View Projects
                            </a>
                        </div>

                        <div class="column is-6 has-text-centered">
                            <img src="/media/demo/hero1.png" alt="">
                        </div>

                    </div>

                </div>
            </div>
        </section>
    </div>

    <!-- Slide 2 -->
    <div class="item">
        <section class="hero is-medium hero-slide slide-2">
            <div class="hero-body">
                <div class="container">

                    <div class="columns is-vcentered">

                        <div class="column is-6">
                            <h1 class="title is-1">Cloud & DevOps Solutions</h1>

                            <p>
                                Scalable infrastructure and automation
                                for modern digital businesses.
                            </p>

                            <br>

                            <a href="/services" class="button is-link is-medium">
                                Explore Services
                            </a>
                        </div>

                        <div class="column is-6 has-text-centered">
                            <img src="/media/demo/hero2.png" alt="">
                        </div>

                    </div>

                </div>
            </div>
        </section>
    </div>

    <!-- Slide 3 -->
    <div class="item">
        <section class="hero is-medium hero-slide slide-3">
            <div class="hero-body">
                <div class="container">

                    <div class="columns is-vcentered">

                        <div class="column is-6">
                            <h1 class="title is-1">Mobile & Web Applications</h1>

                            <p>
                                High-performance applications designed
                                for exceptional user experience.
                            </p>

                            <br>

                            <a href="/contact-us" class="button is-link is-medium">
                                Start Your Project
                            </a>
                        </div>

                        <div class="column is-6 has-text-centered">
                            <img src="/media/demo/hero3.png" alt="">
                        </div>

                    </div>

                </div>
            </div>
        </section>
    </div>

</div>

</section>

{!! blocks_html('homepage_middle') !!}

{!! blocks_html('homepage_bottom') !!}

@endsection
