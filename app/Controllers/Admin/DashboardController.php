<?php

namespace App\Controllers\Admin;

class DashboardController
{
    public static function index()
    {
        echo \Flight::get("blade")->render("admin.dashboard");
    }
}
