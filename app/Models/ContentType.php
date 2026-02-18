<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'has_categories',
        'has_tags',
        'is_system',
    ];
}
