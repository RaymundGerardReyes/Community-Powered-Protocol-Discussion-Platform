<?php

namespace App\Http\Resources;

use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Thread
 */
class ThreadResource extends JsonResource
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
            'protocol_id' => $this->protocol_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'is_pinned' => (bool) $this->is_pinned,
            'views_count' => (int) $this->views_count,
            'replies_count' => (int) $this->replies_count,
            'votes_count' => (int) $this->votes_count,
            'author' => new UserResource($this->whenLoaded('user')),
            'protocol' => new ProtocolResource($this->whenLoaded('protocol')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
