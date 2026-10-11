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
        if (!$user || !$user->hasPermission($this->permission)) {
            // The third arg mirrors Flight's own convention: halt() only
            // exits for real outside of PHPUnit runs.
            $actuallyExit = empty(getenv('PHPUNIT_TEST'));
            \Flight::halt(403, 'Forbidden. You do not have the required permission: ' . $this->permission, $actuallyExit);
            if ($actuallyExit) {
                exit;
            }
        }
    }
}
