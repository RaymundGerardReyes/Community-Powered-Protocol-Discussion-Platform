<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

class Thread extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'protocol_id',
        'user_id',
        'title',
        'slug',
        'content',
        'is_pinned',
        'views_count',
        'replies_count',
        'votes_count',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'views_count' => 'integer',
            'replies_count' => 'integer',
            'votes_count' => 'integer',
        ];
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function scopeSortedBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'top' => $query->orderByDesc('votes_count'),
            'replies' => $query->orderByDesc('replies_count'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('is_pinned')->orderByDesc('created_at'),
        };
    }

    /**
     * Get the indexable data array for Scout.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'protocol_id' => (int) $this->protocol_id,
            'title' => $this->title,
            'content' => $this->content,
            'is_pinned' => (bool) $this->is_pinned,
            'votes_count' => (int) $this->votes_count,
            'replies_count' => (int) $this->replies_count,
            'created_at' => $this->created_at?->timestamp ?? time(),
        ];
    }
}
