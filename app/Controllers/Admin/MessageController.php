<?php

namespace App\Controllers\Admin;

use App\Middleware\AdminMiddleware;
use App\Models\ContactMessage;
use Flight;

class MessageController
{
    /**
     * Display a listing of the contact messages.
     */
    public static function index()
    {
        $messages = ContactMessage::latest('created_at')->get();

        echo Flight::get('blade')->render('admin.messages.index', compact('messages'));
    }
}