<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\ApiToken;
use App\Core\Auth;
use App\Middleware\RequireApiToken;

class ApiTokenExpiryTest extends TestCase
{
    protected $user;
    protected $plainTextToken;
    protected $apiToken;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPUNIT_TEST=1');

        $this->user = User::firstOrCreate(
            ['email' => 'api-expiry@example.com'],
            ['username' => 'apiexpiry', 'name' => 'API Expiry', 'password' => password_hash('password', PASSWORD_DEFAULT)]
        );

        $this->plainTextToken = 'expiry-token-' . bin2hex(random_bytes(10));
        $this->apiToken = $this->user->apiTokens()->create([
            'name' => 'Expiry Token',
            'token' => hash('sha256', $this->plainTextToken),
        ]);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->plainTextToken;
        \Flight::response()->status(200);
    }

    protected function tearDown(): void
    {
        $this->apiToken->delete();
        $this->user->forceDelete();
        unset($_SERVER['HTTP_AUTHORIZATION']);
        Auth::setStatelessUser(null);
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    protected function runMiddleware(): void
    {
        (new RequireApiToken())->before();
    }

    public function test_valid_token_passes_and_sets_stateless_user()
    {
        $this->runMiddleware();

        $this->assertSame(200, \Flight::response()->status());
        $this->assertNotNull(Auth::user());
        $this->assertSame($this->user->id, Auth::user()->id);

        $this->apiToken->refresh();
        $this->assertNotNull($this->apiToken->last_used_at);
    }

    public function test_expired_token_is_rejected()
    {
        $this->apiToken->expires_at = \Illuminate\Support\Carbon::now()->subHour();
        $this->apiToken->save();
        $this->assertTrue($this->apiToken->isExpired());

        $this->runMiddleware();

        $this->assertSame(401, \Flight::response()->status());
    }

    public function test_last_used_at_write_is_throttled()
    {
        $this->runMiddleware();

        $this->apiToken->refresh();
        $firstWrite = $this->apiToken->last_used_at;

        // Immediate second request: within the 60s throttle window.
        sleep(1);
        \Flight::response()->status(200);
        $this->runMiddleware();

        $this->apiToken->refresh();
        $this->assertEquals(
            $firstWrite->format('YmdHis'),
            $this->apiToken->last_used_at->format('YmdHis'),
            'last_used_at must not be rewritten inside the throttle window'
        );

        // Older than 60s: the write happens again.
        $this->apiToken->last_used_at = \Illuminate\Support\Carbon::now()->subSeconds(120);
        $this->apiToken->save();

        \Flight::response()->status(200);
        $this->runMiddleware();

        $this->apiToken->refresh();
        $this->assertTrue(
            $this->apiToken->last_used_at->greaterThan(\Illuminate\Support\Carbon::now()->subSeconds(10)),
            'last_used_at must be rewritten once outside the throttle window'
        );
    }
}
