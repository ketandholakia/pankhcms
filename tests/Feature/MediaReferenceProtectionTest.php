<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\MediaController;
use App\Models\GlobalBlock;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * T8 content-integrity guard: deleting a media file that is still
 * referenced must be refused (file + row preserved); unreferenced media
 * deletes normally. Matching is boundary-aware — a URL is never matched
 * inside a longer filename, so deletion is never blocked by
 * filename-substring false positives.
 */
class MediaReferenceProtectionTest extends TestCase
{
    private array $mediaIds = [];
    private array $pageIds = [];
    private array $blockIds = [];
    private array $files = [];
    private array $settingKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200)->clearBody();
    }

    protected function tearDown(): void
    {
        foreach ($this->mediaIds as $id) {
            Media::find($id)?->delete();
        }
        foreach ($this->pageIds as $id) {
            Page::find($id)?->delete();
        }
        foreach ($this->blockIds as $id) {
            GlobalBlock::find($id)?->delete();
        }
        foreach ($this->settingKeys as $key) {
            DB::table('settings')->where('key', $key)->delete();
        }
        foreach ($this->files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->mediaIds = [];
        $this->pageIds = [];
        $this->blockIds = [];
        $this->files = [];
        $this->settingKeys = [];
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    /** Create a media row plus its physical file, as upload would. */
    private function makeMedia(string $url): Media
    {
        $media = Media::create([
            'filename' => basename($url),
            'original_name' => basename($url),
            'mime_type' => 'image/png',
            'size' => 10,
            'url' => $url,
        ]);
        $this->mediaIds[] = $media->id;

        $filePath = dirname(__DIR__, 2) . '/public' . $url;
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, 'png-bytes');
        $this->files[] = $filePath;

        return $media;
    }

    private function deleteMedia(Media $media): void
    {
        $request = new \flight\net\Request();
        $request->url = '/admin/media';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection([]);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        (new MediaController())->delete((int) $media->id);
    }

    private function makePage(array $data): Page
    {
        $page = Page::create(array_merge([
            'title' => 'RefPage ' . bin2hex(random_bytes(2)),
            'slug' => 'refpage-' . bin2hex(random_bytes(2)),
            'status' => 'published',
        ], $data));
        $this->pageIds[] = $page->id;
        return $page;
    }

    /* --------------------------------------------- referenced → refused */

    public function test_deletion_refused_when_referenced_in_page_content_json()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $this->makePage([
            'content_json' => json_encode([
                ['type' => 'text', 'html' => '<p><img src="' . $media->url . '" alt="x"></p>'],
            ]),
        ]);

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status(), 'a referenced file must be refused');
        $this->assertNotNull(Media::find($media->id), 'the media row must be preserved');
        $this->assertFileExists(dirname(__DIR__, 2) . '/public' . $media->url, 'the file must be preserved');
    }

    public function test_deletion_refused_when_referenced_as_featured_image()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $this->makePage(['featured_image' => $media->url]);

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status());
        $this->assertNotNull(Media::find($media->id));
    }

    public function test_deletion_refused_when_referenced_in_legacy_content_column()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $this->makePage(['content' => '<p><img src="' . $media->url . '"></p>']);

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status());
        $this->assertNotNull(Media::find($media->id));
    }

    public function test_deletion_refused_when_referenced_in_global_block_string_content()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $suffix = bin2hex(random_bytes(2));
        $block = GlobalBlock::create([
            'name' => 'refblock-' . $suffix,
            'slug' => 'refblock-' . $suffix,
            'title' => 'RefBlock ' . $suffix,
            'content' => '<div><img src="' . $media->url . '"></div>',
        ]);
        $this->blockIds[] = $block->id;

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status());
        $this->assertNotNull(Media::find($media->id));
    }

    public function test_deletion_refused_when_referenced_in_global_block_json_content()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $suffix = bin2hex(random_bytes(2));
        $block = GlobalBlock::create([
            'name' => 'refblockjson-' . $suffix,
            'slug' => 'refblockjson-' . $suffix,
            'title' => 'RefBlockJson ' . $suffix,
            'content' => json_encode(['html' => '<img src="' . $media->url . '">']),
        ]);
        $this->blockIds[] = $block->id;

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status());
    }

    public function test_deletion_refused_when_referenced_in_settings()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        DB::table('settings')->updateOrInsert(
            ['key' => 'logo_path_test_reference'],
            ['value' => $media->url]
        );
        $this->settingKeys[] = 'logo_path_test_reference';

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status());
        $this->assertNotNull(Media::find($media->id));
    }

    public function test_refusal_message_names_the_referencing_page()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $page = $this->makePage([
            'content_json' => json_encode([['type' => 'text', 'html' => '<img src="' . $media->url . '">']]),
        ]);

        $this->deleteMedia($media);

        $body = json_decode((string) \Flight::response()->getBody(), true);
        $this->assertSame(409, \Flight::response()->status());
        $this->assertNotEmpty($body['references'] ?? [], 'the refusal must list the references');
        $this->assertStringContainsString($page->title, implode("\n", $body['references']));
    }

    public function test_query_string_variant_still_counts_as_reference()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');
        $this->makePage([
            'content_json' => json_encode([['type' => 'text', 'html' => '<img src="' . $media->url . '?v=2">']]),
        ]);

        $this->deleteMedia($media);

        $this->assertSame(409, \Flight::response()->status(), 'src with a query string still references the file');
    }

    /* ------------------------------------------ unreferenced → deleted */

    public function test_unreferenced_media_deletes_file_and_row()
    {
        $media = $this->makeMedia('/uploads/media/' . bin2hex(random_bytes(8)) . '.png');

        $this->deleteMedia($media);

        $this->assertSame(200, \Flight::response()->status());
        $this->assertNull(Media::find($media->id), 'the media row must be deleted');
        $this->assertFileDoesNotExist(dirname(__DIR__, 2) . '/public' . $media->url, 'the file must be deleted');
    }

    /* ------------------------------- no filename-substring false positives */

    public function test_longer_filename_is_not_blocked_by_shorter_names_reference()
    {
        // Page references aaaa1111.png. A DIFFERENT file whose name
        // extends it (aaaa11112.png) must remain deletable: its URL is
        // not the referenced URL, and boundary matching must not confuse
        // the two.
        $referenced = $this->makeMedia('/uploads/media/aaaa1111.png');
        $longer = $this->makeMedia('/uploads/media/aaaa11112.png');
        $this->makePage([
            'content_json' => json_encode([['type' => 'text', 'html' => '<img src="' . $referenced->url . '">']]),
        ]);

        $this->deleteMedia($longer);

        $this->assertSame(200, \Flight::response()->status(), 'a distinct longer filename must stay deletable');
        $this->assertNull(Media::find($longer->id));
        $this->assertNotNull(Media::find($referenced->id), 'the actually referenced file must be untouched');
        $this->files[] = dirname(__DIR__, 2) . '/public' . $referenced->url; // teardown removes it
    }

    public function test_shorter_filename_is_not_blocked_by_longer_names_reference()
    {
        // Page references bbbb2222.png. Deleting bbbb22.png (whose name
        // is a prefix of the referenced one) must also stay possible.
        $referenced = $this->makeMedia('/uploads/media/bbbb2222.png');
        $shorter = $this->makeMedia('/uploads/media/bbbb22.png');
        $this->makePage([
            'content_json' => json_encode([['type' => 'text', 'html' => '<img src="' . $referenced->url . '">']]),
        ]);

        $this->deleteMedia($shorter);

        $this->assertSame(200, \Flight::response()->status());
        $this->assertNull(Media::find($shorter->id));
        $this->assertNotNull(Media::find($referenced->id));
        $this->files[] = dirname(__DIR__, 2) . '/public' . $referenced->url;
    }

    public function test_content_references_url_boundary_matrix()
    {
        $method = new \ReflectionMethod(MediaController::class, 'contentReferencesUrl');
        $method->setAccessible(true);
        $url = '/uploads/media/abc123.png';

        $positives = [
            '<img src="' . $url . '">',
            '<img src=' . $url . '>',
            $url,
            $url . '?v=1',
            $url . '#frag',
            $url . '/extra',        // path-info style still references it
            'https://cdn.example.com' . $url,
            "text\n" . $url . "\n",
        ];
        foreach ($positives as $haystack) {
            $this->assertTrue($method->invoke(null, $haystack, $url), "must match: $haystack");
        }

        $negatives = [
            '/uploads/media/abc1234.png',   // longer filename
            '/uploads/media/abc123.pngx',   // extended extension
            '/uploads/media/xabc123.png',   // different filename, suffix of ours
            '/uploads/media/',
            strtoupper('/uploads/media/ABC123.PNG'),
            '',
        ];
        foreach ($negatives as $haystack) {
            $this->assertFalse($method->invoke(null, $haystack, $url), "must NOT match: $haystack");
        }
    }
}
