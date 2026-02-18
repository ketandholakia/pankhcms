@extends('layouts.site')

@section('content')

<section class="section">
  <div class="container">
    <h1 class="title">Contact Us</h1>
    <p class="subtitle">Have a question? We'd love to hear from you.</p>

    <form id="contactForm">

      <div class="field">
        <label class="label">Name</label>
        <div class="control">
          <input class="input" name="name" required>
        </div>
      </div>

      <div class="field">
        <label class="label">Email</label>
        <div class="control">
          <input class="input" type="email" name="email" required>
        </div>
      </div>

      <div class="field">
        <label class="label">Subject</label>
        <div class="control">
          <input class="input" name="subject">
        </div>
      </div>

      <div class="field">
        <label class="label">Message</label>
        <div class="control">
          <textarea class="textarea" name="message" required></textarea>
        </div>
      </div>

      <button class="button is-primary">
        Send Message
      </button>

    </form>

    <div id="formStatus" class="mt-4"></div>

  </div>
</section>

<script>
document.getElementById('contactForm')
.addEventListener('submit', async function(e) {
  e.preventDefault();

  const status = document.getElementById('formStatus');
  status.innerHTML = ''; // Clear previous status

  const res = await fetch('/contact', {
    method: 'POST',
    body: new FormData(this)
  });

  const data = await res.json();

  if (res.ok && data.success) {
    status.innerHTML = '<div class="notification is-success">Message sent! We will get back to you shortly.</div>';
    this.reset();
  } else {
    status.innerHTML = '<div class="notification is-danger">' + (data.error || 'An unknown error occurred.') + '</div>';
  }
});
</script>

@endsection