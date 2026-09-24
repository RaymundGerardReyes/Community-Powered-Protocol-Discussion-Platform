<?php

namespace App\Repositories\Eloquent;

use App\Models\Thread;
use App\Repositories\Contracts\ThreadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * ThreadRepository
 * Encapsulates thread query complexity (pinned priority, comments nesting, sorting).
 */
class ThreadRepository implements ThreadRepositoryInterface
{
    public function paginateForProtocol(int $protocolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Thread::query()
            ->where('protocol_id', $protocolId)
            ->with('user');

        if (! empty($filters['search'])) {
            $searchTerm = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                    ->orWhere('content', 'like', $searchTerm);
            });
        }

        $query->sortedBy($filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    public function findByIdWithReplies(int $threadId): Thread
    {
        return Thread::with([
            'user',
            'protocol',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->with(['user', 'replies.user', 'replies.replies.user'])
                    ->orderBy('created_at', 'asc');
            },
        ])->findOrFail($threadId);
    }
}
