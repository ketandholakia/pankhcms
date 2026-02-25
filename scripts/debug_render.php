<?php
require __DIR__ . '/../vendor/autoload.php';
\App\Core\Bootstrap::init();
session_init();
 $slug = 'test-page';
 $page = \App\Models\Page::where('slug', $slug)->first();
 if (! $page) {
	 $page = \App\Models\Page::create([
		 'title' => 'Test Page',
		 'slug' => $slug,
		 'content' => '<p>Hello</p>',
		 'type' => 'page',
		 'status' => 'published',
	 ]);
	 echo "Created page id={$page->id}\n";
 }

 // Call the public controller method which will invoke the private renderPage internally
 ob_start();
 \App\Controllers\Site\SiteController::page($slug);
 $out = ob_get_clean();
 file_put_contents(__DIR__ . '/debug_render_output.html', $out);
 echo "Wrote debug output to scripts/debug_render_output.html\n";