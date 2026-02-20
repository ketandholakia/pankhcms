<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'templates';
    protected $fillable = ['name', 'content_json', 'description', 'is_active'];
    public $timestamps = false;
}
