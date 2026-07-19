<?php

namespace App\Middleware;

use App\Core\Auth;

class RequirePermission
{
    protected $permission;

    public function __construct($permission)
    {
        $this->permission = $permission;
    }

    public function before()
    {
        if (!Auth::check()) {
            \Flight::redirect("/admin/login");
            exit;
        }

        $user = Auth::user();
        if (!$user->hasPermission($this->permission)) {
            \Flight::halt(403, 'Forbidden. You do not have the required permission: ' . $this->permission);
            exit;
        }
    }
}
