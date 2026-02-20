<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTypeField extends Model
{
    protected $table = 'content_type_fields';
    protected $fillable = [
        'content_type_id',
        'name',
        'label',
        'type',
        'options',
        'required',
        'sort_order',
    ];

    public function contentType()
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }
}
