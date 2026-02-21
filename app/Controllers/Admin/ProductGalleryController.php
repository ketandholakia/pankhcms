<?php
namespace App\Controllers\Admin;

use App\Models\ProductGallery;

class ProductGalleryController
{
    public function index()
    {
        $galleries = ProductGallery::orderBy('sort_order')->get();
        echo \Flight::get('blade')->render('admin.product_gallery.index', compact('galleries'));
    }

    public function create()
    {
        echo \Flight::get('blade')->render('admin.product_gallery.create');
    }

    public function store()
    {
        $data = $_POST;
        $file = $_FILES['image'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                \Flight::redirect('/admin/product-gallery?status=invalid-type');
                return;
            }
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir = dirname(__DIR__, 3) . '/public/uploads/product_gallery/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = $dir . $name;
            move_uploaded_file($file['tmp_name'], $path);
            $data['image_path'] = '/uploads/product_gallery/' . $name;
        }
        ProductGallery::create([
            'title' => $data['title'] ?? '',
            'image_path' => $data['image_path'] ?? '',
            'caption' => $data['caption'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'active' => !empty($data['active']) ? 1 : 0,
        ]);
        \Flight::redirect('/admin/product-gallery');
    }

    public function edit($id)
    {
        $gallery = ProductGallery::find($id);
        if (!$gallery) {
            \Flight::redirect('/admin/product-gallery?status=not-found');
            return;
        }
        echo \Flight::get('blade')->render('admin.product_gallery.edit', compact('gallery'));
    }

    public function update($id)
    {
        $gallery = ProductGallery::find($id);
        if (!$gallery) {
            \Flight::redirect('/admin/product-gallery?status=not-found');
            return;
        }
        $data = $_POST;
        $file = $_FILES['image'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                \Flight::redirect('/admin/product-gallery?status=invalid-type');
                return;
            }
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir = dirname(__DIR__, 3) . '/public/uploads/product_gallery/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = $dir . $name;
            move_uploaded_file($file['tmp_name'], $path);
            $gallery->image_path = '/uploads/product_gallery/' . $name;
        }
        $gallery->title = $data['title'] ?? '';
        $gallery->caption = $data['caption'] ?? '';
        $gallery->sort_order = (int)($data['sort_order'] ?? 0);
        $gallery->active = !empty($data['active']) ? 1 : 0;
        $gallery->save();
        \Flight::redirect('/admin/product-gallery');
    }

    public function delete($id)
    {
        $gallery = ProductGallery::find($id);
        if ($gallery) {
            $gallery->delete();
        }
        \Flight::redirect('/admin/product-gallery');
    }
}
