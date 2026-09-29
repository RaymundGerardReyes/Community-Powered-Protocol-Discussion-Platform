<?php

namespace App\Repositories\Eloquent;

use App\Models\Protocol;
use App\Repositories\Contracts\ProtocolRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Collection;
use Typesense\Client as TypesenseClient;

/**
 * ProtocolRepository
 * Encapsulates search and database query complexity (Typesense-first discovery with resilient SQL fallback).
 */
class ProtocolRepository implements ProtocolRepositoryInterface
{
    public function __construct(
        protected ?TypesenseClient $typesense = null
    ) {
        if ($this->typesense === null && app()->bound(TypesenseClient::class)) {
            $this->typesense = app(TypesenseClient::class);
        }
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // 1. Attempt Typesense-first catalog discovery when driver is active
        if (config('scout.driver') === 'typesense' && $this->typesense !== null) {
            try {
                $page = (int) ($filters['page'] ?? request('page', 1));
                $searchQuery = ! empty($filters['search']) ? $filters['search'] : '*';

                $filterBy = [];
                if (! empty($filters['status'])) {
                    $filterBy[] = 'status:=' . $filters['status'];
                } else {
                    $filterBy[] = 'status:=published';
                }

                if (! empty($filters['category'])) {
                    $filterBy[] = 'category:=' . $filters['category'];
                }

                $sortBy = match ($filters['sort'] ?? null) {
                    'top', 'upvoted' => 'votes_count:desc',
                    'rating' => 'average_rating:desc',
                    'reviews' => 'reviews_count:desc',
                    'oldest' => 'created_at:asc',
                    default => 'created_at:desc',
                };

                $searchParams = [
                    'q' => $searchQuery,
                    'query_by' => 'title,description,tags',
                    'filter_by' => implode(' && ', $filterBy),
                    'sort_by' => $sortBy,
                    'page' => $page,
                    'per_page' => $perPage,
                ];

                $results = $this->typesense->collections('protocol')->documents()->search($searchParams);

                $found = (int) ($results['found'] ?? 0);
                $hits = $results['hits'] ?? [];
                $ids = array_map(fn($hit) => (int) $hit['document']['id'], $hits);

                if (empty($ids)) {
                    return new ConcretePaginator(
                        collect([]),
                        $found,
                        $perPage,
                        $page,
                        ['path' => ConcretePaginator::resolveCurrentPath()]
                    );
                }

                $records = Protocol::with('user')
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                $ordered = collect($ids)
                    ->map(fn($id) => $records->get($id))
                    ->filter()
                    ->values();

                return new ConcretePaginator(
                    $ordered,
                    $found,
                    $perPage,
                    $page,
                    ['path' => ConcretePaginator::resolveCurrentPath()]
                );
            } catch (\Throwable) {
                // Fallback to relational SQL query below
            }
        }

        // 2. Resilient SQL Database Fallback
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

        // Search
        if (! empty($filters['search'])) {
            $searchTerm = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$searchTerm]);
            });
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
        if (config('scout.driver') === 'typesense' && $this->typesense !== null) {
            try {
                $results = $this->typesense->collections('protocol')->documents()->search([
                    'q' => '*',
                    'query_by' => 'title,description',
                    'filter_by' => 'status:=published',
                    'sort_by' => 'votes_count:desc',
                    'per_page' => $limit,
                ]);

                $ids = array_map(fn($hit) => (int) $hit['document']['id'], $results['hits'] ?? []);
                if (! empty($ids)) {
                    $records = Protocol::with('user')->whereIn('id', $ids)->get()->keyBy('id');
                    return collect($ids)->map(fn($id) => $records->get($id))->filter()->values();
                }
            } catch (\Throwable) {
                // Fallback to SQL
            }
        }

        return Protocol::published()
            ->with('user')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }
}
