<?php

namespace App\Controllers;

use App\Models\ContactMessage;
use Flight;

class ContactController
{
    /**
     * Display the contact form.
     */
    public static function show()
    {
        session_init();

        // Math captcha; the expected answer lives only in the session.
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION['contact_captcha_expected'] = (string) ($a + $b);

        echo Flight::get('blade')->render('site.contact', ['captcha_a' => $a, 'captcha_b' => $b]);
    }

    /**
     * Handle the contact form submission.
     */
    public static function submit()
    {
        session_init();

        $request = Flight::request();
        $data = $request->data;

        $errors = [];

        $ip = client_ip();

        // Per-IP rate limiting before any expensive work.
        if (!self::checkRateLimit($ip)) {
            Flight::json(['error' => 'Too many messages submitted. Please try again later.'], 429);
            return;
        }

        if (!empty($data->website)) {
            // Honeypot filled in — treat as spam but stay silent about it.
            self::registerHit($ip);
            Flight::json(['error' => 'Invalid submission.'], 422);
            return;
        }

        if (empty($data->name)) {
            $errors[] = 'Name is required.';
        }
        if (empty($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (empty($data->message)) {
            $errors[] = 'A message is required.';
        }

        $expectedCaptcha = isset($_SESSION['contact_captcha_expected']) ? (string) $_SESSION['contact_captcha_expected'] : '';
        $providedCaptcha = trim((string) ($data->captcha ?? ''));
        if ($expectedCaptcha === '' || $providedCaptcha === '' || !hash_equals($expectedCaptcha, $providedCaptcha)) {
            $errors[] = 'Captcha answer is incorrect.';
        }

        if (!empty($errors)) {
            self::registerHit($ip);
            Flight::json(['error' => implode(' ', $errors)], 422);
            return;
        }

        self::registerHit($ip);

        ContactMessage::create([
            'name'       => trim($data->name),
            'email'      => trim($data->email),
            'subject'    => trim($data->subject ?? ''),
            'message'    => trim($data->message),
            'ip'         => $ip,
            'user_agent' => $request->user_agent,
        ]);

        self::sendContactNotificationEmail(
            trim((string) $data->name),
            trim((string) $data->email),
            trim((string) ($data->subject ?? '')),
            trim((string) $data->message),
            (string) ($request->ip ?? ''),
            (string) ($request->user_agent ?? '')
        );

        unset($_SESSION['contact_captcha_expected']);

        Flight::json(['success' => true]);
    }

    /**
     * Simple per-IP file rate limit: max 5 submissions per 10 minutes.
     */
    private static function checkRateLimit(string $ip): bool
    {
        $data = self::readRateLimit($ip);
        return (int) $data['count'] < 5;
    }

    private static function registerHit(string $ip): void
    {
        $now = time();
        $window = 600; // 10 minutes

        $data = self::readRateLimit($ip);
        if ($now - (int) $data['first'] > $window) {
            $data = ['first' => $now, 'count' => 0];
        }
        $data['count'] = (int) $data['count'] + 1;

        $path = dirname(__DIR__, 2) . '/storage/cache/contact_rate/';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        $file = $path . hash('sha256', 'contact|' . $ip) . '.json';
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return;
        }
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    private static function readRateLimit(string $ip): array
    {
        $file = dirname(__DIR__, 2) . '/storage/cache/contact_rate/' . hash('sha256', 'contact|' . $ip) . '.json';
        if (!is_file($file)) {
            return ['first' => time(), 'count' => 0];
        }

        $decoded = json_decode((string) @file_get_contents($file), true);
        return is_array($decoded) ? $decoded : ['first' => time(), 'count' => 0];
    }

    private static function sendContactNotificationEmail(
        string $name,
        string $email,
        string $subject,
        string $message,
        string $ip,
        string $userAgent
    ): void {
        $recipient = trim((string) setting('admin_email', ''));
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $safeName = str_replace(["\r", "\n"], ' ', $name);
        $safeEmail = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'no-reply@localhost';
        $siteName = (string) setting('site_name', 'PankhCMS');
        // Subject and site name end up in mail headers; strip CR/LF to
        // prevent header injection.
        $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
        $safeSiteName = str_replace(["\r", "\n"], ' ', $siteName);
        $mailSubject = '[' . $safeSiteName . '] New Contact Message';
        if ($safeSubject !== '') {
            $mailSubject .= ': ' . $safeSubject;
        }

        $bodyLines = [
            'You received a new contact form submission.',
            '',
            'Name: ' . $name,
            'Email: ' . $email,
            'Subject: ' . ($subject !== '' ? $subject : '-'),
            'IP: ' . ($ip !== '' ? $ip : '-'),
            'User Agent: ' . ($userAgent !== '' ? $userAgent : '-'),
            '',
            'Message:',
            $message,
        ];
        $body = implode("\n", $bodyLines);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $safeName . ' <' . $safeEmail . '>',
            'Reply-To: ' . $safeEmail,
            'X-Mailer: PHP/' . phpversion(),
        ];

        @mail($recipient, $mailSubject, $body, implode("\r\n", $headers));
    }
}