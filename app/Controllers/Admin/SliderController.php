<?php

namespace App\Controllers\Admin;

use App\Models\SliderImage;

class SliderController
{
    public function index()
    {
        $sliders = SliderImage::orderBy('sort_order')->get();
        echo \Flight::get('blade')->render('admin.slider.index', compact('sliders'));
    }

    public function create()
    {
        echo \Flight::get('blade')->render('admin.slider.create');
    }

    public function store()
    {
        $data = $_POST;
        $file = $_FILES['image'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                \Flight::redirect('/admin/slider?status=invalid-type');
                return;
            }
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir = dirname(__DIR__, 3) . '/public/uploads/slider/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = $dir . $name;
            move_uploaded_file($file['tmp_name'], $path);
            $data['image_path'] = '/uploads/slider/' . $name;
        }
        SliderImage::create([
            'image_path' => $data['image_path'] ?? '',
            'caption' => $data['caption'] ?? '',
            'link' => $data['link'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'active' => !empty($data['active']) ? 1 : 0,
        ]);
        \Flight::redirect('/admin/slider');
    }

    public function edit($id)
    {
        $slider = SliderImage::find($id);
        if (!$slider) {
            \Flight::redirect('/admin/slider?status=not-found');
            return;
        }
        echo \Flight::get('blade')->render('admin.slider.edit', compact('slider'));
    }

    public function update($id)
    {
        $slider = SliderImage::find($id);
        if (!$slider) {
            \Flight::redirect('/admin/slider?status=not-found');
            return;
        }
        $data = $_POST;
        $file = $_FILES['image'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                \Flight::redirect('/admin/slider?status=invalid-type');
                return;
            }
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir = dirname(__DIR__, 3) . '/public/uploads/slider/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = $dir . $name;
            move_uploaded_file($file['tmp_name'], $path);
            $slider->image_path = '/uploads/slider/' . $name;
        }
        $slider->caption = $data['caption'] ?? '';
        $slider->link = $data['link'] ?? '';
        $slider->sort_order = (int)($data['sort_order'] ?? 0);
        $slider->active = !empty($data['active']) ? 1 : 0;
        $slider->save();
        \Flight::redirect('/admin/slider');
    }

    public function delete($id)
    {
        $slider = SliderImage::find($id);
        if ($slider) {
            $slider->delete();
        }
        \Flight::redirect('/admin/slider');
    }
}
