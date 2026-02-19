<div class="container-fluid bg-footer bg-primary text-white mt-5">
<div class="container py-5">

<div class="row">

<div class="col-md-4">
<h4>Contact</h4>
<p>{{ setting('contact_address') }}</p>
<p>{{ setting('contact_email') }}</p>
<p>{{ setting('contact_phone') }}</p>
</div>

<div class="col-md-4">
<h4>Quick Links</h4>
{!! render_menu('footer') !!}
</div>

<div class="col-md-4 text-center">
<h4>Newsletter</h4>

<form method="POST" action="/contact">
<input class="form-control mb-2"
name="email" placeholder="Your Email">

<button class="btn btn-secondary">Subscribe</button>
</form>

</div>

</div>
</div>
</div>

<div class="container-fluid bg-dark text-white py-4 text-center">
© {{ date('Y') }} {{ setting('site_name') }}
</div>
