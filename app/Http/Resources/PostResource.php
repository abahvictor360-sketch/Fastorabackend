<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Post
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'heroImage' => $this->heroImage ? new MediaResource($this->heroImage) : null,
            'content' => $this->content,
            'readingTimeMinutes' => $this->readingTimeMinutes(),
            'featured' => (bool) $this->featured,
            'showJournalNote' => (bool) $this->show_journal_note,
            'tags' => collect($this->tags ?? [])->pluck('tag')->values(),
            'categories' => $this->whenLoaded(
                'categories',
                fn () => $this->categories->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'slug' => $c->slug])->values(),
                [],
            ),
            'authors' => $this->whenLoaded(
                'authors',
                fn () => $this->authors->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
                [],
            ),
            'status' => $this->status,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'canonicalUrl' => $this->meta_canonical_url,
                'noindex' => (bool) $this->meta_noindex,
                'image' => $this->metaImage ? new MediaResource($this->metaImage) : null,
            ],
        ];
    }

    /**
     * 200 words per minute, rounded up so a short post never reads as
     * "0 min read". Stripped of HTML tags first — `content` is rich text, and
     * counting markup as words would inflate the estimate.
     */
    private function readingTimeMinutes(): int
    {
        $wordCount = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($wordCount / 200));
    }
}
