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
