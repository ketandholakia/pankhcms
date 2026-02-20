<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';
    protected $fillable = [
        'filename', 'original_name', 'mime_type', 'size', 'url', 'user_id', 'alt', 'title', 'description'
    ];
    public $timestamps = true;
}
