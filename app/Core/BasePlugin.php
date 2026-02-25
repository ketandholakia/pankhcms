<?php

namespace App\Core;

class BasePlugin
{
    protected $meta = [];
    protected $dir = '';

    public function __construct(array $meta = [], string $dir = '')
    {
        $this->meta = $meta;
        $this->dir = $dir;
    }

    public function register()
    {
        // register services, bindings
    }

    public function boot()
    {
        // register routes, hooks, admin pages
    }

    public function activate()
    {
        // run migrations
    }

    public function deactivate()
    {
        // cleanup when deactivated
    }

    public function uninstall()
    {
        // remove DB tables and files
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getDir(): string
    {
        return $this->dir;
    }
}
