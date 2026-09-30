<?php

namespace App\Http\Resources;

use App\Models\Protocol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Protocol
 */
class ProtocolResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->description,
            'category' => $this->category,
            'tags' => (array) ($this->metadata['tags'] ?? ($this->category ? [strtolower($this->category)] : [])),
            'version' => $this->version,
            'status' => $this->status,
            'votes_count' => (int) $this->votes_count,
            'score' => (int) $this->score,
            'reviews_count' => (int) $this->reviews_count,
            'average_rating' => (float) $this->average_rating,
            'rating' => (float) $this->average_rating,
            'metadata' => $this->metadata,
            'author' => new UserResource($this->whenLoaded('user')),
            'threads' => ThreadResource::collection($this->whenLoaded('threads')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
