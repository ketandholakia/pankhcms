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
        $request = Flight::request();
        $data = $request->data;

        $errors = [];
        if (empty($data->name)) {
            $errors[] = 'Name is required.';
        }
        if (empty($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (empty($data->message)) {
            $errors[] = 'A message is required.';
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

        Flight::json(['success' => true]);
    }
}