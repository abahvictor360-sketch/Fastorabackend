<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TeamMember
 */
class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'role' => $this->role,
            'bio' => $this->bio,
            'photo' => $this->photo ? new MediaResource($this->photo) : null,
            'email' => $this->email,
            // Rows an editor left half-filled would render as a button pointing
            // nowhere, so drop anything without both a platform and a URL here
            // rather than making the frontend defend against it.
            'socials' => collect($this->socials ?? [])
                ->filter(fn ($social) => filled($social['platform'] ?? null) && filled($social['url'] ?? null))
                ->map(fn ($social) => [
                    'platform' => $social['platform'],
                    'url' => $social['url'],
                ])
                ->values()
                ->all(),
            'order' => $this->order,
            'status' => $this->status,
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
}
