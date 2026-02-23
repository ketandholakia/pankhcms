<?php
// scripts/debug_page_update.php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
require __DIR__ . '/../app/database.php';
require __DIR__ . '/../app/Helpers/functions.php';
require __DIR__ . '/../app/helpers.php';

// Mock Flight
class MockFlightRequest {
    public $data;
    function __construct() { $this->data = new class { function getData(){ return $_POST; } }; }
}
// Map Flight methods
Flight::map('request', function(){ return new MockFlightRequest(); });
Flight::map('redirect', function($url){ echo "Redirecting to $url\n"; });
// Mock blade service
Flight::register('blade', 'stdClass', [], function($blade){
    $blade->render = function($v, $d=[]) {
        echo "Rendering $v\n";
        if(isset($d['errors'])) { echo "ERRORS: "; print_r($d['errors']); }
    };
});

// Test Data
$id = 36;
$page = App\Models\Page::find($id);
if(!$page) { echo "Page $id not found.\n"; exit; }

$_POST = [
    'title' => $page->title . " (Debug Update)",
    'slug' => $page->slug,
    'type' => $page->type,
    'featured_image' => '/uploads/media/DEBUG_UPDATE_' . time() . '.jpg',
    'content_json' => '[]',
];

echo "Before Update: featured_image=" . ($page->featured_image ?? 'NULL') . "\n";
echo "Sending POST featured_image=" . $_POST['featured_image'] . "\n";

$controller = new App\Controllers\Admin\PageController();
$controller->update($id);

$page = App\Models\Page::find($id);
echo "After Update: featured_image=" . ($page->featured_image ?? 'NULL') . "\n";
