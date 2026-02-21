<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGallery extends Model
{
    protected $table = 'product_galleries';
    protected $fillable = [
        'title', 'image_path', 'caption', 'sort_order', 'active'
    ];
}
