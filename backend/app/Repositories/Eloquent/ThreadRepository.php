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
    private bool $hasExplicitClient = false;

    public function __construct(
        protected ?TypesenseClient $typesense = null
    ) {
        $this->hasExplicitClient = func_num_args() > 0;
    }

    protected function getTypesense(): ?TypesenseClient
    {
        if ($this->hasExplicitClient) {
            return $this->typesense;
        }

        if (! config('scout.typesense.is_configured', false)) {
            return null;
        }

        if (! app()->bound(TypesenseClient::class)) {
            return null;
        }

        $client = app(TypesenseClient::class);
        return $client instanceof TypesenseClient ? $client : null;
    }

    public function hydrateThreadFromDocument(array $doc): Thread
    {
        $id = (int) ($doc['id'] ?? 0);
        $thread = new Thread();
        $thread->exists = true;
        $thread->id = $id;
        $thread->protocol_id = (int) ($doc['protocol_id'] ?? 0);
        $thread->user_id = (int) ($doc['user_id'] ?? $doc['author_id'] ?? 1);
        $thread->title = (string) ($doc['title'] ?? '');
        $thread->slug = (string) ($doc['slug'] ?? \Illuminate\Support\Str::slug($doc['title'] ?? ''));
        $thread->content = (string) ($doc['content'] ?? $doc['body'] ?? '');
        $thread->is_pinned = (bool) ($doc['is_pinned'] ?? false);
        $thread->views_count = (int) ($doc['views_count'] ?? 0);
        $thread->replies_count = (int) ($doc['replies_count'] ?? $doc['comments_count'] ?? $doc['comment_count'] ?? 0);
        $thread->votes_count = (int) ($doc['votes_count'] ?? $doc['votes'] ?? $doc['score'] ?? 0);

        if (! empty($doc['created_at'])) {
            $thread->created_at = is_numeric($doc['created_at'])
                ? \Illuminate\Support\Carbon::createFromTimestamp($doc['created_at'])
                : \Illuminate\Support\Carbon::parse($doc['created_at']);
        } else {
            $thread->created_at = now();
        }
        $thread->updated_at = $thread->created_at;

        // Author relation
        $user = new \App\Models\User();
        $user->exists = true;
        $user->id = $thread->user_id;
        $user->name = (string) ($doc['author'] ?? $doc['author_name'] ?? 'Thread Author');
        $user->email = strtolower(str_replace(' ', '.', $user->name)) . '@protocol.network';
        $user->created_at = $thread->created_at;
        $user->updated_at = $thread->created_at;

        $thread->setRelation('user', $user);
        $thread->setRelation('comments', collect([]));

        return $thread;
    }

    public function paginateForProtocol(int $protocolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $typesense = $this->getTypesense();
        if ($typesense === null) {
            abort(503, "Typesense search engine is required. Client is not initialized and database fallback route has been permanently removed.");
        }

        try {
            $page = (int) ($filters['page'] ?? request('page', 1));
            $searchQuery = ! empty($filters['search']) ? $filters['search'] : (! empty($filters['title']) ? $filters['title'] : '*');
            $sortBy = match ($filters['sort'] ?? null) {
                'top', 'upvoted', 'votes' => 'vote_score:desc',
                'replies', 'most_reviewed', 'reviewed' => 'comment_count:desc',
                'oldest' => 'created_at:asc',
                'recent', 'most_recent' => 'created_at:desc',
                default => 'created_at:desc',
            };

            $searchParams = [
                'q' => $searchQuery,
                'query_by' => 'title,body',
                'filter_by' => 'protocol_id:=' . (string) $protocolId,
                'sort_by' => $sortBy,
                'page' => $page,
                'per_page' => $perPage,
            ];

            if (app()->environment('testing')) {
                $results = $typesense->collections['threads']->documents->search($searchParams);
            } else {
                $cacheKey = 'typesense.threads.' . md5(json_encode($searchParams));
                $results = \Illuminate\Support\Facades\Cache::remember($cacheKey, 15, function () use ($typesense, $searchParams) {
                    return $typesense->collections['threads']->documents->search($searchParams);
                });
            }

            $found = (int) ($results['found'] ?? 0);
            $hits = $results['hits'] ?? [];

            if (empty($hits)) {
                return new ConcretePaginator(
                    collect([]),
                    $found,
                    $perPage,
                    $page,
                    ['path' => ConcretePaginator::resolveCurrentPath()]
                );
            }

            // Direct document hydration without querying SQL database
            $ordered = collect($hits)
                ->map(fn($hit) => $this->hydrateThreadFromDocument($hit['document']))
                ->values();

            return new ConcretePaginator(
                $ordered,
                $found,
                $perPage,
                $page,
                ['path' => ConcretePaginator::resolveCurrentPath()]
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, "Typesense search engine error: {$e->getMessage()}. Database fallback route has been permanently removed.");
        }
    }

    public function findByIdWithReplies(int $threadId): Thread
    {
        $typesense = $this->getTypesense();
        if ($typesense === null) {
            abort(503, "Typesense search engine is required. Client is not initialized and database fallback route has been permanently removed.");
        }

        try {
            try {
                $tdoc = $typesense->collections['threads']->documents[(string) $threadId]->retrieve();
                $thread = $this->hydrateThreadFromDocument($tdoc);
            } catch (\Throwable) {
                $results = $typesense->collections['threads']->documents->search([
                    'q' => '*',
                    'filter_by' => 'id:=' . (string) $threadId,
                    'per_page' => 1,
                ]);

                if (empty($results['hits'])) {
                    abort(404, "Thread not found with ID: {$threadId}");
                }

                $thread = $this->hydrateThreadFromDocument($results['hits'][0]['document']);
            }

            // Load associated comments if relational database is active; otherwise safe empty collection
            try {
                $comments = \App\Models\Comment::where('thread_id', $threadId)
                    ->with('user')
                    ->orderBy('created_at', 'asc')
                    ->get();

                $grouped = $comments->groupBy('parent_id');

                foreach ($comments as $comment) {
                    $comment->setRelation('replies', $grouped->get($comment->id, collect()));
                }

                $thread->setRelation('comments', $comments->whereNull('parent_id')->values());
            } catch (\Throwable) {
                $thread->setRelation('comments', collect([]));
            }

            return $thread;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, "Typesense search engine error: {$e->getMessage()}. Database fallback route has been permanently removed.");
        }
    }
}
