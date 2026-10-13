<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'hero_image_media_id',
        'content',
        'tags',
        'status',
        'featured',
        'show_journal_note',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_canonical_url',
        'meta_noindex',
        'meta_image_media_id',
    ];

    protected $casts = [
        'meta_noindex' => 'boolean',
        'featured' => 'boolean',
        'show_journal_note' => 'boolean',
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_image_media_id');
    }

    public function metaImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'meta_image_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Publicly visible posts: fully published, or scheduled posts whose
     * publish time has already arrived (mirrors Payload's schedulePublish —
     * a "scheduled" post becomes visible the moment its time passes, with no
     * separate job needed to flip its status).
     */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'published')
                ->orWhere(function ($q2) {
                    $q2->where('status', 'scheduled')->where('published_at', '<=', now());
                });
        });
    }
}
