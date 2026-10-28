<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Central mail sender for transactional email (password resets, contact
 * notifications).
 *
 * With MAIL_HOST configured, mail goes out through SMTP via PHPMailer
 * (MAIL_HOST/MAIL_PORT/MAIL_USERNAME/MAIL_PASSWORD/MAIL_ENCRYPTION,
 * MAIL_FROM_ADDRESS/MAIL_FROM_NAME). With MAIL_HOST empty the legacy
 * PHP mail() built-in is used — the only situation where local mail
 * delivery is expected to work.
 *
 * Every header-bound value (recipient, subject, from name, reply-to) is
 * stripped of CR/LF before it reaches the transport, so header
 * injection through user input is impossible. Failures are logged to
 * storage/logs/ instead of being swallowed; send() returns bool so
 * callers may degrade gracefully.
 */
class Mailer
{
    /** Standard mail-submission port, used when MAIL_PORT is unset or invalid. */
    public const DEFAULT_SMTP_PORT = 587;

    /**
     * The SMTP port to use: an explicit positive MAIL_PORT when set,
     * otherwise 587 (mail submission).
     */
    public static function resolvePort(): int
    {
        $port = (int) env('MAIL_PORT', self::DEFAULT_SMTP_PORT);
        return $port > 0 ? $port : self::DEFAULT_SMTP_PORT;
    }

    public static function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $to = self::sanitizeHeader($to);
        $subject = self::sanitizeHeader($subject);
        $fromAddress = self::sanitizeHeader(trim((string) env('MAIL_FROM_ADDRESS', '')) ?: 'no-reply@localhost');
        $fromName = self::sanitizeHeader(trim((string) env('MAIL_FROM_NAME', '')) ?: (string) setting('site_name', 'PankhCMS'));

        $replyTo = isset($options['reply_to']) ? self::sanitizeHeader((string) $options['reply_to']) : '';

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::log("not sending: invalid recipient <{$to}>");
            return false;
        }

        if (trim((string) env('MAIL_HOST', '')) === '') {
            return self::sendViaMailFunction($to, $subject, $body, $fromAddress, $fromName, $replyTo);
        }

        return self::sendViaSmtp($to, $subject, $body, $fromAddress, $fromName, $replyTo, (int) ($options['timeout'] ?? 10));
    }

    private static function sendViaSmtp(
        string $to,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName,
        string $replyTo,
        int $timeout
    ): bool {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = trim((string) env('MAIL_HOST', ''));
            $mail->Port = self::resolvePort();
            $mail->Timeout = max(1, $timeout);

            $username = trim((string) env('MAIL_USERNAME', ''));
            if ($username !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = (string) env('MAIL_PASSWORD', '');
            }

            $encryption = strtolower(trim((string) env('MAIL_ENCRYPTION', 'tls')));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'none' || $encryption === '') {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);
            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->Sender = $fromAddress;

            return $mail->send();
        } catch (\Throwable $e) {
            self::log('SMTP send failed to <' . $to . '>: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendViaMailFunction(
        string $to,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName,
        string $replyTo
    ): bool {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromAddress . '>',
        ];
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        // @ is intentional here: failures are reported via the return
        // value and logged below, never shown to the visitor.
        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$ok) {
            self::log('mail() returned false for <' . $to . '> subject "' . $subject . '"');
        }
        return $ok;
    }

    /**
     * Header values must never contain CR/LF (header injection).
     */
    public static function sanitizeHeader(string $value): string
    {
        return str_replace(["\r", "\n", "\0"], ' ', trim($value));
    }

    private static function log(string $message): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/mailer.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND
        );
    }
}
