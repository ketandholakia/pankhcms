<?php

class ContactFormPlugin
{
    public function boot()
    {
        // Load routes
        require __DIR__ . '/routes.php';

        // Load admin pages
        if ($this->isAdmin()) {
            require __DIR__ . '/admin.php';
        }
    }

    private function isAdmin()
    {
        return strpos($_SERVER['REQUEST_URI'], '/admin') === 0;
    }
}
