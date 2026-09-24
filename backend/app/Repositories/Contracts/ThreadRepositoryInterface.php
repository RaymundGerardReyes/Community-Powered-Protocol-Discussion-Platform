<?php

namespace App\Repositories\Contracts;

use App\Models\Thread;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ThreadRepositoryInterface
{
    /**
     * Paginate threads for a specific protocol with optional sorting.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForProtocol(int $protocolId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a thread by ID, eager loading author, protocol, and top-level comments with nested replies.
     */
    public function findByIdWithReplies(int $threadId): Thread;
}
