<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'role',
        'bio',
        'photo_media_id',
        'email',
        'socials',
        'order',
        'status',
        'meta_title',
        'meta_description',
        'meta_canonical_url',
        'meta_noindex',
        'meta_image_media_id',
    ];

    protected $casts = [
        'socials' => 'array',
        'meta_noindex' => 'boolean',
        'order' => 'integer',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function metaImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'meta_image_media_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
