<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $table = 'redirects';
    protected $fillable = ['old_url', 'new_url', 'type'];
    public $timestamps = false;
}
