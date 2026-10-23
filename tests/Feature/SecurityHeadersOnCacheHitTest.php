<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\SecurityHeaders;
use App\Middleware\PageCache;

/**
 * A page served from the pre-bootstrap cache must carry the same
 * site-wide security headers as a normal response: the early path exits
 * before Flight's before('start') hook ever fires, so the headers have
 * to be sent by PageCache itself.
 *
 * header() is a no-op in CLI phpunit, so this test performs a real HTTP
 * request against the real front controller using PHP's built-in server.
 */
class SecurityHeadersOnCacheHitTest extends TestCase
{
    /** @var resource|null */
    private $server = null;
    private string $envFileCreated = '';
    private string $seededCacheFile = '';
    private array $serverBackup = [];

    protected function tearDown(): void
    {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
            $this->server = null;
        }
        if ($this->seededCacheFile !== '' && is_file($this->seededCacheFile)) {
            @unlink($this->seededCacheFile);
        }
        if ($this->envFileCreated !== '') {
            @unlink($this->envFileCreated);
            $this->envFileCreated = '';
        }
        // Restore the superglobals this test borrows; unsetting them
        // breaks later `new Request()` calls in the same process.
        foreach (['REQUEST_METHOD', 'HTTP_HOST', 'REQUEST_URI', 'HTTPS'] as $key) {
            if (array_key_exists($key, $this->serverBackup)) {
                $_SERVER[$key] = $this->serverBackup[$key];
            } else {
                unset($_SERVER[$key]);
            }
        }
        $_COOKIE = $this->serverBackup['_COOKIE'] ?? [];
        $_GET = $this->serverBackup['_GET'] ?? [];
        $this->serverBackup = [];
        parent::tearDown();
    }

    private function startServer(int $port)
    {
        $root = dirname(__DIR__, 2);
        $cmd = [
            PHP_BINARY,
            '-S', '127.0.0.1:' . $port,
            $root . '/public/index.php',
        ];
        $spec = [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']];
        $server = proc_open($cmd, $spec, $pipes, $root);
        if (!is_resource($server)) {
            return false;
        }

        // Wait for the server to accept connections (max ~3s).
        $deadline = microtime(true) + 3.0;
        do {
            $sock = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.25);
            if ($sock !== false) {
                fclose($sock);
                return $server;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);

        proc_terminate($server);
        proc_close($server);
        return false;
    }

    /**
     * GET over plain HTTP; returns [body, headers[]] or null on failure.
     */
    private function httpGet(string $url, int $timeoutSeconds = 5): ?array
    {
        $ctx = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'header' => "Accept: text/html\r\n",
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false || !isset($http_response_header)) {
            return null;
        }
        return [$body, $http_response_header];
    }

    public function test_early_cache_hit_response_includes_security_headers()
    {
        $root = dirname(__DIR__, 2);
        $envPath = $root . '/.env';

        // The front controller redirects to /install without a .env; CI
        // has none, so provide a minimal one (removed in tearDown). An
        // existing developer .env is left untouched.
        if (!is_file($envPath)) {
            $port0 = 0; // APP_URL is only used for base-path detection
            file_put_contents($envPath, "APP_URL=http://127.0.0.1\nDB_CONNECTION=sqlite\nDB_DATABASE=:memory:\n");
            $this->envFileCreated = $envPath;
        }

        $server = false;
        for ($attempt = 0; $attempt < 3 && $server === false; $attempt++) {
            $port = random_int(21000, 39999);
            $server = $this->startServer($port);
        }
        if ($server === false) {
            $this->markTestSkipped('PHP built-in server could not be started (port binding failed)');
        }
        $this->server = $server;

        // Seed a cache entry for /security-headers-probe under the exact
        // host:port the request will carry, using the production key logic.
        $host = '127.0.0.1:' . $port;
        $this->serverBackup = [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'HTTPS' => $_SERVER['HTTPS'] ?? null,
            '_COOKIE' => $_COOKIE,
            '_GET' => $_GET,
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['REQUEST_URI'] = '/security-headers-probe';
        unset($_SERVER['HTTPS']);
        $_COOKIE = [];
        $_GET = [];

        $keyMethod = new \ReflectionMethod(PageCache::class, 'cacheKey');
        $keyMethod->setAccessible(true);
        $dirMethod = new \ReflectionMethod(PageCache::class, 'cacheDir');
        $dirMethod->setAccessible(true);
        $file = $dirMethod->invoke(null) . '/' . $keyMethod->invoke(null, '/security-headers-probe', []) . '.html';
        file_put_contents($file, '<html>cache-hit-security-headers</html>');
        $this->seededCacheFile = $file;

        [$body, $headers] = $this->httpGet("http://{$host}/security-headers-probe");

        $headerLines = [];
        foreach ($headers as $h) {
            if (stripos($h, 'HTTP/') === 0) {
                continue;
            }
            $headerLines[] = $h;
        }
        $headerBlob = implode("\n", $headerLines);

        $this->assertNotNull($body, 'request against the built-in server failed');
        $this->assertSame('<html>cache-hit-security-headers</html>', $body, 'the seeded cache entry must be served');
        $this->assertStringContainsString('X-Pankh-Cache: HIT', $headerBlob, 'response must come from the early cache');
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', $headerBlob);
        $this->assertStringContainsString('X-Frame-Options: SAMEORIGIN', $headerBlob);
        $this->assertStringContainsString('Referrer-Policy: strict-origin-when-cross-origin', $headerBlob);
        // HSTS is HTTPS-only (SecurityHeaders::headers() documents why).
        $this->assertStringNotContainsString('Strict-Transport-Security', $headerBlob);
    }

    public function test_security_headers_computation_matches_the_documented_contract()
    {
        $_SERVER['HTTPS'] = 'off';
        $plain = SecurityHeaders::headers();
        $this->assertSame('nosniff', $plain['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $plain['X-Frame-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $plain['Referrer-Policy']);
        $this->assertArrayNotHasKey('Strict-Transport-Security', $plain);

        $_SERVER['HTTPS'] = 'on';
        $secure = SecurityHeaders::headers();
        $this->assertSame('max-age=31536000; includeSubDomains', $secure['Strict-Transport-Security']);
        unset($_SERVER['HTTPS']);
    }
}
