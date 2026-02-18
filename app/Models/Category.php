<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name','slug','parent_id'];
    public $timestamps = false;

    public function pages()
    {
        return $this->belongsToMany(Page::class, 'page_categories');
    }
}
