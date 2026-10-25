<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\PageController;
use App\Models\ContentType;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * T4: server-side allowlists for page status and type. Only statuses the
 * application understands (draft/published — scheduling is a future
 * published_at on a published page) and known types (core types plus
 * registered ContentType slugs) are accepted; anything else returns an
 * actionable validation error. Empty/absent values stay untouched so
 * legacy rows with an empty type keep editing cleanly.
 */
class PageStatusTypeValidationTest extends TestCase
{
    /** @var int[] */
    private array $typeIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');
        \Flight::response()->status(200);
    }

    protected function tearDown(): void
    {
        foreach ($this->typeIds as $id) {
            ContentType::find($id)?->delete();
        }
        $this->typeIds = [];
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    /**
     * Invoke the private getValidatedInput() directly for precise
     * assertions on the errors/data it produces.
     */
    private function validate(array $raw, bool $isUpdate = false): array
    {
        $request = new \flight\net\Request();
        $request->url = '/admin/pages';
        $request->method = 'POST';
        $request->data = new \flight\util\Collection($raw);

        $engine = \Flight::app();
        $loaderProp = (new \ReflectionClass($engine))->getProperty('loader');
        $loader = $loaderProp->getValue($engine);
        $instancesProp = (new \ReflectionClass($loader))->getProperty('instances');
        $instances = $instancesProp->getValue($loader);
        $instances['request'] = $request;
        $instancesProp->setValue($loader, $instances);

        $method = new \ReflectionMethod(PageController::class, 'getValidatedInput');
        $method->setAccessible(true);

        return $method->invoke(new PageController(), $isUpdate);
    }

    public function test_invalid_statuses_are_rejected_with_actionable_error()
    {
        foreach (['publishd', 'PUBLISHED', 'scheduled', 'archive', 'visible'] as $bad) {
            $result = $this->validate([
                'title' => 'T',
                'status' => $bad,
            ]);

            $this->assertArrayHasKey('errors', $result, "status '$bad' must be rejected");
            $this->assertStringContainsString(
                "'status' must be either 'draft' or 'published'.",
                implode("\n", $result['errors'])
            );
        }
    }

    public function test_valid_statuses_pass_and_are_kept()
    {
        foreach (['draft', 'published'] as $good) {
            $result = $this->validate([
                'title' => 'T',
                'status' => $good,
            ]);

            $this->assertArrayNotHasKey('errors', $result, "status '$good' must be accepted");
            $this->assertSame($good, $result['status']);
        }
    }

    public function test_empty_or_absent_status_stays_untouched()
    {
        // Empty string is how legacy rows submitted "no status"; absence
        // lets store() apply its published default.
        $result = $this->validate(['title' => 'T', 'status' => '']);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertSame('', $result['status']);

        $result = $this->validate(['title' => 'T']);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayNotHasKey('status', $result);
    }

    public function test_unknown_type_is_rejected_with_actionable_error()
    {
        $result = $this->validate([
            'title' => 'T',
            'type' => 'newstype-does-not-exist',
        ]);

        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString(
            "'type' is not a known content type.",
            implode("\n", $result['errors'])
        );
    }

    public function test_core_types_and_registered_content_type_slugs_are_accepted()
    {
        foreach (['page', 'post', 'feature', 'product', 'products'] as $core) {
            $result = $this->validate(['title' => 'T', 'type' => $core]);
            $this->assertArrayNotHasKey('errors', $result, "core type '$core' must be accepted");
        }

        $type = ContentType::create([
            'name' => 'ValidatedType ' . bin2hex(random_bytes(2)),
            'slug' => 'validated-type-' . bin2hex(random_bytes(2)),
        ]);
        $this->typeIds[] = $type->id;

        $result = $this->validate(['title' => 'T', 'type' => $type->slug]);
        $this->assertArrayNotHasKey('errors', $result, "a registered ContentType slug must be accepted");
    }

    public function test_empty_or_absent_type_stays_untouched_for_legacy_rows()
    {
        $result = $this->validate(['title' => 'T', 'type' => '']);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertSame('', $result['type']);

        $result = $this->validate(['title' => 'T']);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayNotHasKey('type', $result);
    }

    public function test_type_allowlist_follows_the_content_types_table()
    {
        // An unregistered slug fails...
        $slug = 'dynamic-type-' . bin2hex(random_bytes(2));
        $this->assertArrayHasKey('errors', $this->validate(['title' => 'T', 'type' => $slug]));

        // ...and passes as soon as the content type is registered.
        $type = ContentType::create([
            'name' => 'DynamicType ' . $slug,
            'slug' => $slug,
        ]);
        $this->typeIds[] = $type->id;

        $result = $this->validate(['title' => 'T', 'type' => $slug]);
        $this->assertArrayNotHasKey('errors', $result);
    }
}
