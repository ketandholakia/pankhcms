<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockPlacement extends Model
{
    protected $table = 'block_placements';
    protected $guarded = [];
    public $timestamps = false;

    public function block()
    {
        return $this->belongsTo(GlobalBlock::class, 'block_id');
    }

    // Accessor for `sort_order` to support code using newer column name
    public function getSortOrderAttribute()
    {
        if (array_key_exists('sort_order', $this->attributes)) {
            return $this->attributes['sort_order'];
        }
        return $this->attributes['order'] ?? null;
    }

    // Mutator so setting `sort_order` writes to existing `order` column
    public function setSortOrderAttribute($value)
    {
        $this->attributes['order'] = $value;
    }

    // Accessor for `location` mapped to existing `section` column
    public function getLocationAttribute()
    {
        if (array_key_exists('location', $this->attributes)) {
            return $this->attributes['location'];
        }
        return $this->attributes['section'] ?? null;
    }

    // Mutator so setting `location` writes to existing `section` column
    public function setLocationAttribute($value)
    {
        $this->attributes['section'] = $value;
    }
}
