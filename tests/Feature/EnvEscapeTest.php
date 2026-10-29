<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Dotenv\Dotenv;

class EnvEscapeTest extends TestCase
{
    /**
     * Round-trip escaped values through phpdotenv itself: the written
     * .env must parse back to the exact original strings.
     */
    #[DataProvider('escapedValueProvider')]
    public function test_env_escape_round_trips_through_phpdotenv(string $raw)
    {
        $envFile = sys_get_temp_dir() . '/env_escape_test_' . uniqid() . '/.env';
        @mkdir(dirname($envFile), 0775, true);

        $content = 'DB_PASSWORD=' . env_escape($raw) . "\n";
        file_put_contents($envFile, $content);

        // Dotenv's unsafe-immutable loader skips variables that already
        // exist in the environment. When the suite is run with DB_PASSWORD
        // exported (MariaDB-backed runs), the real password would shadow
        // the fixture — clear every carrier first.
        putenv('DB_PASSWORD');
        unset($_ENV['DB_PASSWORD'], $_SERVER['DB_PASSWORD']);

        $dotenv = Dotenv::createUnsafeImmutable(dirname($envFile));
        $dotenv->load();

        $readBack = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

        // Clean up the variable so other tests are unaffected.
        putenv('DB_PASSWORD');
        unset($_ENV['DB_PASSWORD'], $_SERVER['DB_PASSWORD']);
        @unlink($envFile);
        @rmdir(dirname($envFile));

        // phpdotenv normalizes CRLF to LF inside quoted values, so the
        // exact round-trip is asserted against the normalized form.
        $this->assertSame(str_replace("\r\n", "\n", $raw), $readBack, 'escaped .env value must parse back to the original');
    }

    public static function escapedValueProvider(): array
    {
        return [
            'plain' => ['simple-value'],
            'backslashes' => ['C:\\Temp\\Path With\\Stuff'],
            'double quotes' => ['He said "hi" loudly'],
            'dollar signs' => ['P@$$w0rd$pecial'],
            'dollar + braces' => ['${HOME} expansion attempt'],
            'newline' => ["line1\nline2"],
            'windows newline' => ["line1\r\nline2"],
            'installer worst case' => ['p@ss"\\$word\nnext'],
        ];
    }
}
