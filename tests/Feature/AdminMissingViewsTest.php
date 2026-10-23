<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Bootstrap;
use App\Models\Media;

/**
 * The media admin and admin error views used to be missing (and the
 * views/admin/media directory itself was swallowed by a bare `media/`
 * rule in .gitignore). These tests render every one of them through the
 * real Blade engine used by Bootstrap::initBlade() so a missing view,
 * a wrong variable contract, or an unescaped field fails loudly.
 */
class AdminMissingViewsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('PHPUNIT_TEST=1');
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        $helpers = new \ReflectionMethod(Bootstrap::class, 'loadHelpers');
        $helpers->invoke(null);

        $initBlade = new \ReflectionMethod(Bootstrap::class, 'initBlade');
        $initBlade->invoke(null);
    }

    private function render(string $view, array $data = []): string
    {
        $blade = \Flight::get('blade');
        $this->assertNotNull($blade, 'initBlade must provide the Blade engine');

        return (string) $blade->render($view, $data);
    }

    public function test_media_index_view_renders_with_controller_contract()
    {
        $media = Media::create([
            'filename' => 'view-test.png',
            'original_name' => 'View Test <img>.png',
            'mime_type' => 'image/png',
            'size' => 1234,
            'url' => '/uploads/media/view-test.png',
        ]);

        try {
            $html = $this->render('admin.media.index', [
                'media' => collect([$media]),
                'search' => '',
                'type' => '',
                'date' => '',
            ]);
        } finally {
            $media->delete();
        }

        $this->assertStringContainsString('/uploads/media/view-test.png', $html);
        // The delete button must POST with CSRF protection.
        $this->assertStringContainsString('action="/admin/media/' . $media->id . '/delete"', $html);
        $this->assertStringContainsString('name="_csrf"', $html);
        // The upload form JS posts to the upload endpoint.
        $this->assertStringContainsString('/admin/media/upload', $html);
        // Filter controls echo the current filter state.
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('name="type"', $html);
        $this->assertStringContainsString('name="date"', $html);
        // Dangerous characters in the original filename are escaped.
        $this->assertStringNotContainsString('<img>.png"', $html);
    }

    public function test_media_index_renders_empty_state()
    {
        $html = $this->render('admin.media.index', [
            'media' => collect(),
            'search' => 'zzz-no-match',
            'type' => '',
            'date' => '',
        ]);

        $this->assertStringContainsString('No media found', $html);
        $this->assertStringContainsString('value="zzz-no-match"', $html);
    }

    public function test_media_picker_view_renders_select_buttons_with_data_attributes()
    {
        $media = Media::create([
            'filename' => 'picker-test.png',
            'original_name' => 'picker-test.png',
            'mime_type' => 'image/png',
            'size' => 10,
            'url' => '/uploads/media/picker-test.png',
        ]);

        try {
            $html = $this->render('admin.media.picker', ['media' => collect([$media])]);
        } finally {
            $media->delete();
        }

        // Must match the contract the picker consumers rely on: the same
        // class and data attributes MediaController::picker() emits for
        // the AJAX grid.
        $this->assertStringContainsString('media-picker-select-btn', $html);
        $this->assertStringContainsString('data-id="' . $media->id . '"', $html);
        $this->assertStringContainsString('data-url="/uploads/media/picker-test.png"', $html);
    }

    public function test_admin_error_views_render_the_message()
    {
        $html404 = $this->render('admin.errors.404', ['message' => 'Page not found.']);
        $this->assertStringContainsString('404', $html404);
        $this->assertStringContainsString('Page not found.', $html404);

        $html403 = $this->render('admin.errors.403', ['message' => 'System content types cannot be deleted.']);
        $this->assertStringContainsString('403', $html403);
        $this->assertStringContainsString('System content types cannot be deleted.', $html403);
    }

    /**
     * The .gitignore rewrite must not ignore source directories: the
     * TinyMCE media plugin (swallowed by the old bare `media/` rule) and
     * the new views/admin/media directory must both be trackable.
     */
    public function test_gitignore_no_longer_swallows_source_directories()
    {
        $repoRoot = dirname(__DIR__, 2);
        $this->assertFileExists($repoRoot . '/public/assets/tinymce/plugins/media/plugin.min.js');

        // check-ignore -q accepts exactly one pathname; probe each source
        // path separately. Exit 1 = not ignored; 0 = ignored (bad).
        foreach ([
            'public/assets/tinymce/plugins/media/index.js',
            'views/admin/media/index.blade.php',
        ] as $path) {
            $exitCode = 0;
            exec(
                'git -C ' . escapeshellarg($repoRoot) . ' check-ignore -q -- ' . escapeshellarg($path),
                $out,
                $exitCode
            );
            $this->assertSame(
                1,
                $exitCode,
                "$path must not be ignored: source directories under public/assets and views must stay trackable"
            );
        }
    }
}
