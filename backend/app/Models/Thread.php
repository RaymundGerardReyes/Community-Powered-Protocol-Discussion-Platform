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
     * Modify the query used to retrieve models when making all of the models searchable.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['user', 'protocol']);
    }

    /**
     * Get the indexable data array for Scout.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $authorName = $this->user ? $this->user->name : 'Anonymous';

        return [
            'id' => (string) $this->id,
            'protocol_id' => (string) $this->protocol_id,
            'user_id' => (int) ($this->user_id ?? 0),
            'author_id' => (int) ($this->user_id ?? 0),
            'author' => $authorName,
            'title' => $this->title,
            'body' => $this->content,
            'content' => $this->content,
            'tags' => ($this->relationLoaded('protocol') && $this->protocol)
                ? (array) ($this->protocol->metadata['tags'] ?? ($this->protocol->category ? [strtolower($this->protocol->category)] : []))
                : [],
            'is_pinned' => (bool) $this->is_pinned,
            'votes_count' => (int) $this->votes_count,
            'vote_score' => (int) $this->votes_count,
            'votes' => (int) $this->votes_count,
            'score' => (int) $this->votes_count,
            'replies_count' => (int) $this->replies_count,
            'comment_count' => (int) $this->replies_count,
            'comments_count' => (int) $this->replies_count,
            'views_count' => (int) ($this->views_count ?? 0),
            'author_name' => $authorName,
            'created_at' => $this->created_at ? $this->created_at->timestamp : time(),
        ];
    }

    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return 'threads';
    }

    /**
     * Get the Typesense collection schema using auto-schema detection and explicit facets.
     *
     * @return array<string, mixed>
     */
    public function typesenseCollectionSchema(): array
    {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'protocol_id', 'type' => 'string', 'facet' => true],
                ['name' => 'user_id', 'type' => 'int32', 'optional' => true],
                ['name' => 'author_id', 'type' => 'int32', 'optional' => true],
                ['name' => 'author', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'author_name', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'body', 'type' => 'string'],
                ['name' => 'content', 'type' => 'string'],
                ['name' => 'tags', 'type' => 'string[]', 'facet' => true, 'optional' => true],
                ['name' => 'votes_count', 'type' => 'int32'],
                ['name' => 'vote_score', 'type' => 'int32', 'optional' => true],
                ['name' => 'votes', 'type' => 'int32', 'optional' => true],
                ['name' => 'score', 'type' => 'int32', 'optional' => true],
                ['name' => 'replies_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'comment_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'comments_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'views_count', 'type' => 'int32', 'optional' => true],
                ['name' => '.*', 'type' => 'auto'],
            ],
            'default_sorting_field' => 'votes_count',
            'enable_nested_fields' => true,
        ];
    }
}
