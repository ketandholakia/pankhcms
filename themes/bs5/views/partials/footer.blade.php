<footer class="footer text-center">
<div class="container">

<div class="row">

<div class="col-lg-4">
<h4>Location</h4>
<p>{{ setting('address') }}</p>
</div>

<div class="col-lg-4">
<h4>Around the Web</h4>
</div>

<div class="col-lg-4">
<h4>About</h4>
<p>{{ setting('site_tagline') }}</p>
</div>

</div>

</div>
</footer>

<div class="copyright py-4 text-center text-white">
<div class="container">
<small>© {{ date('Y') }} {{ setting('site_name') }}</small>
</div>
</div>
