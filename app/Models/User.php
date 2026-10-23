<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ["username", "name", "email", "password"];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        return !! $role->intersect($this->roles)->count();
    }

    public function hasPermission($permission)
    {
        // loadMissing is idempotent: the first call eager-loads
        // roles.permissions for this instance, later calls are free.
        $this->loadMissing('roles.permissions');

        return $this->roles->filter(function ($role) use ($permission) {
            return $role->permissions->contains('name', $permission);
        })->isNotEmpty();
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }
}
