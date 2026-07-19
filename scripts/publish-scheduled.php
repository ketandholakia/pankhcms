<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/database.php';
// Need Bootstrap to load helpers and Event class? Let's just require it.
require_once __DIR__ . '/../app/Core/Bootstrap.php';
\App\Core\Bootstrap::init(); // Initialize everything so Event and plugins work

use App\Models\Page;

$due = Page::where('status', 'published')
    ->whereNotNull('published_at')
    ->where('published_at', '<=', \Illuminate\Support\Carbon::now())
    ->whereNull('published_notified_at')
    ->get(); // these are already visible via scopeVisible — this is just for firing events/notifications

foreach ($due as $page) {
    \Event::dispatch('page.published', $page);
    $page->published_notified_at = \Illuminate\Support\Carbon::now();
    $page->save();
}

echo "Processed " . count($due) . " scheduled pages.\n";
