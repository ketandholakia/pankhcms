<?php

namespace App\Controllers\Admin;

use App\Models\SliderImage;
use Illuminate\Database\Capsule\Manager as Capsule;

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
        self::ensureSliderSchema();

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
        $payload = [
            'image_path' => $data['image_path'] ?? '',
            'link' => $data['link'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ];

        $payload = array_merge($payload, self::buildTextPayload($data));

        if (self::hasColumn('active')) {
            $payload['active'] = !empty($data['active']) ? 1 : 0;
        }

        SliderImage::create($payload);
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
        self::ensureSliderSchema();

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
        $textPayload = self::buildTextPayload($data);
        foreach ($textPayload as $key => $value) {
            $slider->{$key} = $value;
        }
        $slider->link = $data['link'] ?? '';
        $slider->sort_order = (int)($data['sort_order'] ?? 0);
        if (self::hasColumn('active')) {
            $slider->active = !empty($data['active']) ? 1 : 0;
        }
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

    private static function buildTextPayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $caption = trim((string) ($data['caption'] ?? ''));

        $payload = [];

        if (self::hasColumn('title')) {
            $payload['title'] = $title !== '' ? $title : $caption;
        }

        if (self::hasColumn('description')) {
            $payload['description'] = $description;
        }

        if (self::hasColumn('caption')) {
            $payload['caption'] = $caption !== '' ? $caption : $title;
        }

        return $payload;
    }

    private static function ensureSliderSchema(): void
    {
        try {
            $schema = Capsule::schema();
            if (!$schema->hasTable('slider_images')) {
                return;
            }

            if (!$schema->hasColumn('slider_images', 'title')) {
                $schema->table('slider_images', function ($table) {
                    $table->string('title')->nullable();
                });
            }

            if (!$schema->hasColumn('slider_images', 'description')) {
                $schema->table('slider_images', function ($table) {
                    $table->text('description')->nullable();
                });
            }

            if (!$schema->hasColumn('slider_images', 'caption')) {
                $schema->table('slider_images', function ($table) {
                    $table->string('caption')->nullable();
                });
            }

            if (!$schema->hasColumn('slider_images', 'active')) {
                $schema->table('slider_images', function ($table) {
                    $table->boolean('active')->default(1);
                });
            }
        } catch (\Throwable $e) {
            // Keep slider management usable even if schema repair fails.
        }
    }

    private static function hasColumn(string $column): bool
    {
        try {
            return Capsule::schema()->hasTable('slider_images')
                && Capsule::schema()->hasColumn('slider_images', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
