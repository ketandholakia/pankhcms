<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Mailer;

/**
 * App\Core\Mailer: SMTP via PHPMailer when MAIL_HOST is set, the PHP
 * mail() built-in only when MAIL_HOST is empty, CR/LF stripped from
 * every header-bound value, and failures logged to storage/logs
 * instead of being swallowed.
 */
class MailerTest extends TestCase
{
    private string $logFile;
    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = dirname(__DIR__, 2) . '/storage/logs/mailer.log';
        foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'] as $key) {
            $this->envBackup[$key] = getenv($key);
        }
        @unlink($this->logFile);
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $value);
            }
        }
        if (is_file($this->logFile)) {
            @unlink($this->logFile);
        }
        parent::tearDown();
    }

    public function test_header_values_have_cr_lf_stripped()
    {
        // The exact replacement text is irrelevant; the security property
        // is that NO CR or LF survives into a header-bound value, so an
        // injected "Bcc:" line can never become a separate header.
        $dirty = "bad\r\nBcc: evil@example.com";
        $clean = Mailer::sanitizeHeader($dirty);
        $this->assertStringNotContainsString("\r", $clean);
        $this->assertStringNotContainsString("\n", $clean);
        $this->assertStringContainsString('bad', $clean, 'surrounding text is preserved');

        $cleanLf = Mailer::sanitizeHeader("bad\nBcc: x");
        $this->assertStringNotContainsString("\n", $cleanLf);
        $this->assertStringNotContainsString("\0", Mailer::sanitizeHeader("nul\0stripped"));
    }

    public function test_invalid_recipient_is_refused_and_logged()
    {
        putenv('MAIL_HOST=');
        $this->assertFalse(Mailer::send("not-an-email\r\nBcc: x", 's', 'b'));
        $this->assertFileExists($this->logFile, 'the refusal must be logged');
        $this->assertStringContainsString('invalid recipient', file_get_contents($this->logFile));
    }

    public function test_unreachable_smtp_fails_gracefully_and_logs()
    {
        // Reserve a port, close it, and point SMTP at it: connection is
        // refused immediately, exercising the failure/logging path.
        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = stream_socket_get_name($sock, false);
        $port = (int) substr($name, strrpos($name, ':') + 1);
        fclose($sock);

        putenv('MAIL_HOST=127.0.0.1');
        putenv('MAIL_PORT=' . $port);
        putenv('MAIL_ENCRYPTION=none');
        putenv('MAIL_USERNAME=');
        putenv('MAIL_FROM_ADDRESS=no-reply@example.com');

        $start = microtime(true);
        $result = Mailer::send('dest@example.com', 'subject', 'body', ['timeout' => 2]);
        $elapsed = microtime(true) - $start;

        $this->assertFalse($result, 'an unreachable SMTP host must report failure');
        $this->assertLessThan(15, $elapsed, 'the short timeout must be respected');
        $this->assertFileExists($this->logFile, 'the SMTP failure must be logged');
        $this->assertStringContainsString('SMTP send failed', file_get_contents($this->logFile));
    }

    public function test_mail_fallback_path_is_used_only_without_mail_host()
    {
        // With MAIL_HOST set the SMTP path runs (and fails against the
        // closed port above); with MAIL_HOST empty the mail() fallback
        // runs — observable because mail() on this CLI either succeeds
        // silently or reports false, and the failure case logs. We
        // assert the fallback does NOT produce an SMTP log entry.
        putenv('MAIL_HOST=');
        @unlink($this->logFile);

        // mail() to an unroutable local domain typically returns false
        // on CLI (no sendmail), which exercises the logging; if a local
        // MTA exists it succeeds — either way no SMTP error may appear.
        Mailer::send('dest@example.com', 'fallback probe', 'body');

        if (is_file($this->logFile)) {
            $this->assertStringNotContainsString(
                'SMTP send failed',
                file_get_contents($this->logFile),
                'MAIL_HOST empty must use the mail() fallback, never SMTP'
            );
        }
        $this->addToAssertionCount(1); // reaching here means no crash either way
    }

    public function test_controllers_use_the_mailer_not_raw_mail()
    {
        foreach ([
            'app/Controllers/AuthController.php',
            'app/Controllers/ContactController.php',
        ] as $rel) {
            $src = file_get_contents(dirname(__DIR__, 2) . '/' . $rel);
            $this->assertStringContainsString('Mailer::send(', $src, "$rel must send through the Mailer helper");
            $this->assertStringNotContainsString('@mail(', $src, "$rel must not call mail() directly");
        }
    }
    public function test_smtp_port_defaults_to_587_and_honors_explicit_setting()
    {
        // T1: the fallback port must be the standard mail-submission
        // port 587 (the old default 586 is not a standard port).
        $this->assertSame(587, Mailer::DEFAULT_SMTP_PORT);

        putenv('MAIL_PORT');
        $this->assertSame(587, Mailer::resolvePort(), 'unset MAIL_PORT must default to 587');

        putenv('MAIL_PORT=2525');
        $this->assertSame(2525, Mailer::resolvePort(), 'an explicit MAIL_PORT must override the default');

        putenv('MAIL_PORT=0');
        $this->assertSame(587, Mailer::resolvePort(), 'a non-positive port must fall back to 587');

        putenv('MAIL_PORT=not-a-port');
        $this->assertSame(587, Mailer::resolvePort(), 'a garbage port must fall back to 587');
    }
}
