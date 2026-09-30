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

    public function hydrateProtocolFromDocument(array $doc): Protocol
    {
        $id = (int) ($doc['id'] ?? 0);
        $protocol = new Protocol();
        $protocol->exists = true;
        $protocol->id = $id;
        $protocol->user_id = (int) ($doc['user_id'] ?? $doc['author_id'] ?? 1);
        $protocol->title = (string) ($doc['title'] ?? '');
        $protocol->slug = (string) ($doc['slug'] ?? \Illuminate\Support\Str::slug($doc['title'] ?? ''));
        $protocol->description = (string) ($doc['description'] ?? '');
        $protocol->category = (string) ($doc['category'] ?? 'General');
        $protocol->version = (string) ($doc['version'] ?? '1.0.0');
        $protocol->status = (string) ($doc['status'] ?? 'published');
        $protocol->score = (int) ($doc['score'] ?? $doc['vote_score'] ?? $doc['votes'] ?? 0);
        $protocol->votes_count = (int) ($doc['votes_count'] ?? $doc['votes'] ?? 0);
        $protocol->reviews_count = (int) ($doc['reviews_count'] ?? 0);
        $protocol->average_rating = (float) ($doc['average_rating'] ?? 0);
        $protocol->metadata = ['tags' => (array) ($doc['tags'] ?? [])];

        if (! empty($doc['created_at'])) {
            $protocol->created_at = is_numeric($doc['created_at'])
                ? \Illuminate\Support\Carbon::createFromTimestamp($doc['created_at'])
                : \Illuminate\Support\Carbon::parse($doc['created_at']);
        } else {
            $protocol->created_at = now();
        }
        $protocol->updated_at = $protocol->created_at;

        // Hydrate author relation directly from indexed document data
        $user = new \App\Models\User();
        $user->exists = true;
        $user->id = $protocol->user_id;
        $user->name = (string) ($doc['author'] ?? $doc['author_name'] ?? 'Protocol Author');
        $user->email = strtolower(str_replace(' ', '.', $user->name)) . '@protocol.network';
        $user->created_at = $protocol->created_at;
        $user->updated_at = $protocol->created_at;

        $protocol->setRelation('user', $user);
        $protocol->setRelation('threads', collect([]));
        $protocol->setRelation('reviews', collect([]));

        return $protocol;
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $typesense = $this->getTypesense();
        if ($typesense === null) {
            abort(503, "Typesense search engine is required. Client is not initialized and database fallback route has been permanently removed.");
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

            $results = $typesense->collections['protocol']->documents->search($searchParams);

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
                ->map(fn($hit) => $this->hydrateProtocolFromDocument($hit['document']))
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

    public function findBySlugOrFail(string $slug): Protocol
    {
        $typesense = $this->getTypesense();
        if ($typesense === null) {
            abort(503, "Typesense search engine is required. Client is not initialized and database fallback route has been permanently removed.");
        }

        try {
            if (is_numeric($slug)) {
                try {
                    $doc = $typesense->collections['protocol']->documents[(string) $slug]->retrieve();
                    $protocol = $this->hydrateProtocolFromDocument($doc);
                } catch (\Throwable) {
                    $results = $typesense->collections['protocol']->documents->search([
                        'q' => '*',
                        'filter_by' => 'id:=' . (string) $slug,
                        'per_page' => 1,
                    ]);
                    if (empty($results['hits'])) {
                        abort(404, "Protocol not found with ID: {$slug}");
                    }
                    $protocol = $this->hydrateProtocolFromDocument($results['hits'][0]['document']);
                }
            } else {
                $results = $typesense->collections['protocol']->documents->search([
                    'q' => $slug,
                    'query_by' => 'slug,title',
                    'filter_by' => 'slug:=' . $slug,
                    'per_page' => 1,
                ]);

                if (empty($results['hits'])) {
                    // Fallback search by normalized title/slug
                    $results = $typesense->collections['protocol']->documents->search([
                        'q' => str_replace('-', ' ', $slug),
                        'query_by' => 'slug,title',
                        'per_page' => 1,
                    ]);
                }

                if (empty($results['hits'])) {
                    abort(404, "Protocol not found with slug: {$slug}");
                }

                $protocol = $this->hydrateProtocolFromDocument($results['hits'][0]['document']);
            }

            // Load associated threads directly from Typesense threads collection
            try {
                $threadResults = $typesense->collections['threads']->documents->search([
                    'q' => '*',
                    'filter_by' => 'protocol_id:=' . (string) $protocol->id,
                    'sort_by' => 'votes_count:desc',
                    'per_page' => 25,
                ]);

                $threads = collect($threadResults['hits'] ?? [])->map(function ($hit) use ($protocol) {
                    $tdoc = $hit['document'];
                    $thread = new \App\Models\Thread();
                    $thread->exists = true;
                    $thread->id = (int) $tdoc['id'];
                    $thread->protocol_id = $protocol->id;
                    $thread->title = (string) ($tdoc['title'] ?? '');
                    $thread->slug = (string) ($tdoc['slug'] ?? \Illuminate\Support\Str::slug($tdoc['title'] ?? ''));
                    $thread->content = (string) ($tdoc['content'] ?? $tdoc['body'] ?? '');
                    $thread->is_pinned = (bool) ($tdoc['is_pinned'] ?? false);
                    $thread->views_count = (int) ($tdoc['views_count'] ?? 0);
                    $thread->replies_count = (int) ($tdoc['replies_count'] ?? $tdoc['comments_count'] ?? $tdoc['comment_count'] ?? 0);
                    $thread->votes_count = (int) ($tdoc['votes_count'] ?? $tdoc['votes'] ?? $tdoc['score'] ?? 0);

                    if (! empty($tdoc['created_at'])) {
                        $thread->created_at = is_numeric($tdoc['created_at'])
                            ? \Illuminate\Support\Carbon::createFromTimestamp($tdoc['created_at'])
                            : \Illuminate\Support\Carbon::parse($tdoc['created_at']);
                    } else {
                        $thread->created_at = now();
                    }
                    $thread->updated_at = $thread->created_at;

                    $tUser = new \App\Models\User();
                    $tUser->exists = true;
                    $tUser->id = (int) ($tdoc['user_id'] ?? $tdoc['author_id'] ?? 1);
                    $tUser->name = (string) ($tdoc['author'] ?? $tdoc['author_name'] ?? 'Thread Author');
                    $tUser->email = strtolower(str_replace(' ', '.', $tUser->name)) . '@protocol.network';
                    $tUser->created_at = $thread->created_at;
                    $tUser->updated_at = $thread->created_at;

                    $thread->setRelation('user', $tUser);
                    $thread->setRelation('comments', collect([]));
                    return $thread;
                });

                $protocol->setRelation('threads', $threads);
            } catch (\Throwable) {
                $protocol->setRelation('threads', collect([]));
            }

            return $protocol;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, "Typesense search engine error: {$e->getMessage()}. Database fallback route has been permanently removed.");
        }
    }

    public function getTopVoted(int $limit = 10): Collection
    {
        $typesense = $this->getTypesense();
        if ($typesense === null) {
            abort(503, "Typesense search engine is required. Client is not initialized and database fallback route has been permanently removed.");
        }

        try {
            $results = $typesense->collections['protocol']->documents->search([
                'q' => '*',
                'query_by' => 'title,description',
                'filter_by' => 'status:=published',
                'sort_by' => 'votes_count:desc',
                'per_page' => $limit,
            ]);

            return collect($results['hits'] ?? [])
                ->map(fn($hit) => $this->hydrateProtocolFromDocument($hit['document']))
                ->values();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, "Typesense search engine error: {$e->getMessage()}. Database fallback route has been permanently removed.");
        }
    }
}
