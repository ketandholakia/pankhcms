<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class SearchPlugin
{
    public function boot()
    {
        Flight::route('GET /search', function () {
            $q = Flight::request()->query->q ?? '';
            $results = [];

            if (!empty($q)) {
                // FTS search on pages_fts
                try {
                    $results = Capsule::select(
                        "SELECT p.*, bm25(pages_fts) as rank 
                         FROM pages_fts 
                         JOIN pages p ON p.id = pages_fts.rowid 
                         WHERE pages_fts MATCH ? 
                         ORDER BY rank 
                         LIMIT 50",
                        [$q]
                    );
                } catch (\Throwable $e) {
                    // Fallback to LIKE if FTS is missing
                    $results = Capsule::table('pages')
                        ->where('title', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%")
                        ->get()
                        ->toArray();
                }
            }

            // In a real app we'd render a view, but for the plugin we can manually render 
            // a custom Blade template if we inject it, or just pass it to the active theme's search view.
            // Let's check if the theme has a 'search' view, else render our own fallback.
            
            $blade = Flight::get('blade');
            if ($blade->exists('search')) {
                echo $blade->render('search', compact('results', 'q'));
            } else {
                // Fallback output
                echo "<h1>Search Results for: " . htmlspecialchars($q) . "</h1>";
                if (empty($results)) {
                    echo "<p>No results found.</p>";
                } else {
                    echo "<ul>";
                    foreach ($results as $result) {
                        $url = rtrim(env('APP_URL'), '/') . '/' . $result->slug;
                        echo "<li><a href='{$url}'>" . htmlspecialchars($result->title) . "</a></li>";
                    }
                    echo "</ul>";
                }
            }
        });
    }
}
