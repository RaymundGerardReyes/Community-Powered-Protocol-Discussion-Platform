<?php

namespace App\Repositories\Eloquent;

use App\Models\Protocol;
use App\Repositories\Contracts\ProtocolRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ProtocolRepository
 * Encapsulates PostgreSQL query complexity reused across API endpoints and
 * search reindex jobs (filtering, sorting, faceting).
 */
class ProtocolRepository implements ProtocolRepositoryInterface
{
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Protocol::query()->with('user');

        // Filter by status (default published for public views)
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->published();
        }

        // Filter by category
        if (! empty($filters['category'])) {
            $query->filterByCategory($filters['category']);
        }

        // Text search across title and description
        if (! empty($filters['search'])) {
            $searchTerm = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm);
            });
        }

        // Apply sorting (top, rating, reviews, oldest, newest)
        $query->sortedBy($filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    public function findBySlugOrFail(string $slug): Protocol
    {
        return Protocol::where('slug', $slug)
            ->with(['user', 'threads.user', 'reviews.user'])
            ->firstOrFail();
    }

    public function getTopVoted(int $limit = 10): Collection
    {
        return Protocol::published()
            ->with('user')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }
}
