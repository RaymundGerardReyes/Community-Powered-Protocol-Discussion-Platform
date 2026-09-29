<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

class Protocol extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'category',
        'version',
        'status',
        'votes_count',
        'score',
        'reviews_count',
        'average_rating',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'average_rating' => 'float',
            'votes_count' => 'integer',
            'score' => 'integer',
            'reviews_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeFilterByCategory(Builder $query, ?string $category): Builder
    {
        if (! $category) {
            return $query;
        }

        $lower = strtolower($category);
        $compact = str_replace(['-', ' ', '_'], '', $lower);

        return $query->where(function (Builder $q) use ($lower, $compact) {
            $q->whereRaw('LOWER(category) = ?', [$lower])
                ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(category, '-', ''), ' ', ''), '_', '')) = ?", [$compact]);
        });
    }

    public function scopeSortedBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'top' => $query->orderByDesc('score'),
            'rating' => $query->orderByDesc('average_rating'),
            'reviews' => $query->orderByDesc('reviews_count'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
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
        return $query->with('user');
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
            'user_id' => (int) ($this->user_id ?? 0),
            'author_id' => (int) ($this->user_id ?? 0),
            'author' => $authorName,
            'author_name' => $authorName,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'tags' => (array) ($this->metadata['tags'] ?? [strtolower($this->category)]),
            'version' => $this->version,
            'status' => $this->status,
            'score' => (int) $this->score,
            'votes' => (int) $this->votes_count,
            'votes_count' => (int) $this->votes_count,
            'vote_score' => (int) $this->votes_count,
            'reviews_count' => (int) $this->reviews_count,
            'average_rating' => (float) $this->average_rating,
            'created_at' => $this->created_at ? $this->created_at->timestamp : time(),
        ];
    }

    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return 'protocol';
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
                ['name' => 'user_id', 'type' => 'int32', 'optional' => true],
                ['name' => 'author_id', 'type' => 'int32', 'optional' => true],
                ['name' => 'author', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'author_name', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'description', 'type' => 'string'],
                ['name' => 'category', 'type' => 'string', 'facet' => true],
                ['name' => 'tags', 'type' => 'string[]', 'facet' => true, 'optional' => true],
                ['name' => 'status', 'type' => 'string', 'facet' => true],
                ['name' => 'score', 'type' => 'int32', 'optional' => true],
                ['name' => 'votes', 'type' => 'int32'],
                ['name' => 'votes_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'vote_score', 'type' => 'int32', 'optional' => true],
                ['name' => 'reviews_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'average_rating', 'type' => 'float', 'optional' => true],
                ['name' => '.*', 'type' => 'auto'],
            ],
            'default_sorting_field' => 'votes',
            'enable_nested_fields' => true,
        ];
    }
}
