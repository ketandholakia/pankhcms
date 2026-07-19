<?php

namespace App\Controllers\Admin;

use App\Models\Role;
use App\Models\Permission;

class RoleController
{
    public static function index()
    {
        $roles = Role::with('permissions')->get();
        echo \Flight::get('blade')->render('admin.roles.index', compact('roles'));
    }

    public static function create()
    {
        $permissions = Permission::all();
        echo \Flight::get('blade')->render('admin.roles.form', ['role' => new Role(), 'permissions' => $permissions, 'action' => '/admin/roles/store']);
    }

    public static function store()
    {
        $data = \Flight::request()->data->getData();
        
        $role = new Role();
        $role->name = $data['name'] ?? 'New Role';
        $role->save();

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        \Flight::redirect('/admin/roles?status=created');
    }

    public static function edit($id)
    {
        $role = Role::with('permissions')->find($id);
        if (!$role) \Flight::redirect('/admin/roles');
        
        $permissions = Permission::all();
        echo \Flight::get('blade')->render('admin.roles.form', ['role' => $role, 'permissions' => $permissions, 'action' => "/admin/roles/update/{$id}"]);
    }

    public static function update($id)
    {
        $role = Role::find($id);
        if (!$role) \Flight::redirect('/admin/roles');

        $data = \Flight::request()->data->getData();
        $role->name = $data['name'] ?? $role->name;
        $role->save();

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        } else {
            $role->permissions()->detach();
        }

        \Flight::redirect('/admin/roles?status=updated');
    }

    public static function destroy($id)
    {
        $role = Role::find($id);
        if ($role && $role->name !== 'Administrator') {
            $role->delete();
        }
        \Flight::redirect('/admin/roles?status=deleted');
    }
}
