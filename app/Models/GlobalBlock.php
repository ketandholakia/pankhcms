<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalBlock extends Model
{
    protected $table = 'global_blocks';
    protected $guarded = [];
    protected $casts = [
        'content' => 'array',
        'show_title' => 'boolean',
    ];
    public $timestamps = false;

    public function placements()
    {
        return $this->hasMany(BlockPlacement::class, 'block_id');
    }
}
