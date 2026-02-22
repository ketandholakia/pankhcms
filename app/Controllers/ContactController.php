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
        echo Flight::get('blade')->render('site.contact');
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

        if (!empty($data->website)) {
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
            Flight::json(['error' => implode(' ', $errors)], 422);
            return;
        }

        ContactMessage::create([
            'name'       => trim($data->name),
            'email'      => trim($data->email),
            'subject'    => trim($data->subject ?? ''),
            'message'    => trim($data->message),
            'ip'         => $request->ip,
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
        $mailSubject = '[' . $siteName . '] New Contact Message';
        if ($subject !== '') {
            $mailSubject .= ': ' . $subject;
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