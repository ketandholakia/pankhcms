<footer class="mt-12 border-t border-slate-200 bg-white">
    <div class="pankh-container py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-900">{{ setting('site_name', 'PankhCMS') }}</p>
                <p class="text-sm text-slate-600">{{ setting('site_tagline', setting('tagline', '')) }}</p>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a class="text-slate-600 hover:text-slate-900" href="/">Home</a>
                <a class="text-slate-600 hover:text-slate-900" href="/contact">Contact</a>
            </div>
        </div>
    </div>
</footer>
