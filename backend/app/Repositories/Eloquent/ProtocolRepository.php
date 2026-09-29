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
 * Encapsulates search and database query complexity.
 * When SCOUT_DRIVER is set to typesense (or ty), strictly routes through Typesense API.
 * When SCOUT_DRIVER is null/database, routes through relational SQL tables.
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
        $driver = (string) config('scout.driver', 'null');
        $isTypesense = $driver === 'typesense' || str_starts_with($driver, 'ty');

        // 1. Typesense-first catalog discovery when driver is active
        if ($isTypesense) {
            if ($this->typesense === null) {
                abort(503, "Typesense search engine is active (SCOUT_DRIVER={$driver}) but client is not initialized.");
            }

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

                $results = $this->typesense->collections['protocol']->documents->search($searchParams);

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
            } catch (\Throwable $e) {
                abort(503, "Typesense search engine error: {$e->getMessage()}");
            }
        }

        // 2. Relational SQL Database Mode (active when SCOUT_DRIVER=null or database)
        $query = Protocol::query()->with('user');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->published();
        }

        if (! empty($filters['category'])) {
            $query->filterByCategory($filters['category']);
        }

        if (! empty($filters['search'])) {
            $searchTerm = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$searchTerm]);
            });
        }

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
        $driver = (string) config('scout.driver', 'null');
        $isTypesense = $driver === 'typesense' || str_starts_with($driver, 'ty');

        if ($isTypesense) {
            if ($this->typesense === null) {
                abort(503, "Typesense search engine is active (SCOUT_DRIVER={$driver}) but client is not initialized.");
            }

            try {
                $results = $this->typesense->collections['protocol']->documents->search([
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

                return collect([]);
            } catch (\Throwable $e) {
                abort(503, "Typesense search engine error: {$e->getMessage()}");
            }
        }

        return Protocol::published()
            ->with('user')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }
}
