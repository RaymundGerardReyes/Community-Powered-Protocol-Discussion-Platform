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

        // Search: use Typesense Scout when driver is active, otherwise fallback to case-insensitive SQL search
        if (! empty($filters['search'])) {
            $usedScout = false;
            if (config('scout.driver') === 'typesense') {
                try {
                    $ids = Protocol::search($filters['search'])->keys()->all();
                    $query->whereIn('id', $ids);
                    $usedScout = true;
                } catch (\Throwable) {
                    $usedScout = false;
                }
            }

            if (! $usedScout) {
                $searchTerm = '%'.mb_strtolower($filters['search']).'%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$searchTerm]);
                });
            }
        }

        // Apply sorting (top, rating, reviews, oldest, newest)
        $query->sortedBy($filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    public function findBySlugOrFail(string $slug): Protocol
    {
        $query = Protocol::query()->with(['user', 'threads.user', 'reviews.user']);

        if (is_numeric($slug)) {
            return $query->where('id', (int) $slug)->orWhere('slug', $slug)->firstOrFail();
        }

        return $query->where('slug', $slug)->firstOrFail();
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
