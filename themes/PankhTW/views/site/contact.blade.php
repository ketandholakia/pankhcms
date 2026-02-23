@php
    session_init();
    $captchaA = random_int(1, 9);
    $captchaB = random_int(1, 9);
    $_SESSION['contact_captcha_expected'] = (string) ($captchaA + $captchaB);
@endphp

<section class="pankh-section">
    <div class="pankh-container grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 pankh-card p-6">
            <h1 class="text-2xl font-semibold">Contact Us</h1>
            <p class="mt-2 text-slate-600">Have a question? Send us a message.</p>

            <form id="contactForm" class="mt-6 space-y-4" novalidate>
                <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">Name *</span>
                        <input name="name" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none ring-0 focus:border-slate-500">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">Email *</span>
                        <input type="email" name="email" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none ring-0 focus:border-slate-500">
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium">Subject</span>
                    <input name="subject" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none ring-0 focus:border-slate-500">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium">Message *</span>
                    <textarea name="message" rows="6" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none ring-0 focus:border-slate-500"></textarea>
                </label>

                <label class="block md:max-w-xs">
                    <span class="mb-1 block text-sm font-medium">Captcha: {{ $captchaA }} + {{ $captchaB }} = ? *</span>
                    <input type="number" name="captcha" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none ring-0 focus:border-slate-500">
                </label>

                <button id="contactSubmitBtn" type="submit" class="pankh-btn-primary">Send Message</button>
            </form>

            <div id="formStatus" class="mt-4" aria-live="polite"></div>
        </div>

        <aside class="pankh-card p-6">
            <h2 class="text-lg font-semibold">Contact Information</h2>
            <p class="mt-3 text-slate-600">{{ setting('site_name', 'PankhCMS') }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ setting('site_tagline', setting('tagline', '')) }}</p>
        </aside>
    </div>
</section>

<script>
(function () {
  const form = document.getElementById('contactForm');
  const status = document.getElementById('formStatus');
  const submitBtn = document.getElementById('contactSubmitBtn');
  if (!form || !status || !submitBtn) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    status.innerHTML = '';

    if (!form.checkValidity()) {
      status.innerHTML = '<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">Please fill required fields.</div>';
      return;
    }

    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Sending...';

    try {
      const response = await fetch('/contact', { method: 'POST', body: new FormData(form) });
      let data = {};
      try { data = await response.json(); } catch (err) { data = {}; }

      if (response.ok && data.success) {
        status.innerHTML = '<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">Message sent successfully.</div>';
        form.reset();
      } else {
        status.innerHTML = '<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">' + (data.error || 'Unable to send message now.') + '</div>';
      }
    } catch (error) {
      status.innerHTML = '<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">Network error. Please try again.</div>';
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  });
})();
</script>
