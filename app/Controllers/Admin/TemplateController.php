<?php

namespace App\Controllers\Admin;

use App\Models\Template;

class TemplateController
{
    public static function index()
    {
        $templates = Template::orderBy('name')->get();
        echo \Flight::get('blade')->render('admin.templates.index', compact('templates'));
    }

    public static function show($id)
    {
        $tpl = Template::findOrFail($id);
        \Flight::json($tpl);
    }

    public static function store()
    {
        $data = \Flight::request()->data->getData();
        Template::create($data);
        \Flight::json(['success' => true]);
    }

    public static function update($id)
    {
        $template = Template::findOrFail($id);
        $data = \Flight::request()->data->getData();
        $template->update($data);
        \Flight::json(['success' => true]);
    }

    public static function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();
        \Flight::redirect('/admin/templates');
    }
}