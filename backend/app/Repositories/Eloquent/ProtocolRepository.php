<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ProtocolRepositoryInterface;
use App\Models\Protocol;

/**
 * ProtocolRepository
 * Encapsulates PostgreSQL query complexity reused across API endpoints and
 * search reindex jobs (filtering, sorting, faceting).
 */
class ProtocolRepository implements ProtocolRepositoryInterface
{
    // TODO: implement query methods used by ProtocolService.
}
