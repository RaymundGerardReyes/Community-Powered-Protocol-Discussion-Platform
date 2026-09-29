<?php

namespace App\Repositories\Eloquent;

use App\Models\Thread;
use App\Repositories\Contracts\ThreadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Typesense\Client as TypesenseClient;

/**
 * ThreadRepository
 * Encapsulates thread query complexity.
 * Strictly routes through Typesense API when SCOUT_DRIVER is typesense or ty.
 * Routes through relational SQL database when SCOUT_DRIVER is null or database.
 */
class ThreadRepository implements ThreadRepositoryInterface
{
    public function __construct(
        protected ?TypesenseClient $typesense = null
    ) {
        if ($this->typesense === null && app()->bound(TypesenseClient::class)) {
            $this->typesense = app(TypesenseClient::class);
        }
    }

    public function paginateForProtocol(int $protocolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $driver = (string) config('scout.driver', 'null');
        $isTypesense = $driver === 'typesense' || str_starts_with($driver, 'ty');

        // 1. Typesense-first search when driver is active
        if ($isTypesense) {
            if ($this->typesense === null) {
                abort(503, "Typesense search engine is active (SCOUT_DRIVER={$driver}) but client is not initialized.");
            }

            try {
                $page = (int) ($filters['page'] ?? request('page', 1));
                $searchQuery = ! empty($filters['search']) ? $filters['search'] : '*';
                $sortBy = match ($filters['sort'] ?? null) {
                    'top' => 'votes_count:desc',
                    'oldest' => 'created_at:asc',
                    default => 'is_pinned:desc,created_at:desc',
                };

                $searchParams = [
                    'q' => $searchQuery,
                    'query_by' => 'title,body,content,tags',
                    'filter_by' => 'protocol_id:=' . (string) $protocolId,
                    'sort_by' => $sortBy,
                    'page' => $page,
                    'per_page' => $perPage,
                ];

                $results = $this->typesense->collections('threads')->documents()->search($searchParams);

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

                $records = Thread::with('user')
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
        $query = Thread::query()
            ->where('protocol_id', $protocolId)
            ->with('user');

        if (! empty($filters['search'])) {
            $searchTerm = '%'.mb_strtolower($filters['search']).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(content) LIKE ?', [$searchTerm]);
            });
        }

        $query->sortedBy($filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    public function findByIdWithReplies(int $threadId): Thread
    {
        $thread = Thread::with(['user', 'protocol'])->findOrFail($threadId);

        $comments = $thread->comments()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        $grouped = $comments->groupBy('parent_id');

        foreach ($comments as $comment) {
            $comment->setRelation('replies', $grouped->get($comment->id, collect()));
        }

        $thread->setRelation('comments', $comments->whereNull('parent_id')->values());

        return $thread;
    }
}
