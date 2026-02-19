<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'parent_id',
        'type',                // ⭐ NEW
        'title',
        'slug',
        'content',
        'content_json',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots',
        'twitter_card',
        'twitter_site',
        'noindex',
        'layout',
        'status',
        'featured_image',
    ];

    public $timestamps = true;

    protected $casts = [
        'noindex' => 'boolean',
    ];

    // =============================
    // Relationships
    // =============================

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'page_categories');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'page_tags');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function contentType()
    {
        return $this->belongsTo(ContentType::class, 'type', 'slug');
    }

    // =============================
    // Scopes (VERY USEFUL)
    // =============================

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePages($query)
    {
        return $query->where('type', 'page');
    }

    public function scopeFeatures($query)
    {
        return $query->where('type', 'feature');
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }
}
