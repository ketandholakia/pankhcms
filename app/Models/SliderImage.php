<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SliderImage extends Model
{
    protected $table = 'slider_images';
    protected $fillable = [
        'image_path', 'caption', 'link', 'sort_order', 'active'
    ];
}
