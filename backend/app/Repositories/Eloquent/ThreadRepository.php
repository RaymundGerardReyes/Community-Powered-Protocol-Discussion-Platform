<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ThreadRepositoryInterface;
use App\Models\Thread;

/**
 * ThreadRepository
 * Encapsulates PostgreSQL query complexity reused across API endpoints and
 * search reindex jobs (filtering, sorting, faceting).
 */
class ThreadRepository implements ThreadRepositoryInterface
{
    // TODO: implement query methods used by ThreadService.
}
