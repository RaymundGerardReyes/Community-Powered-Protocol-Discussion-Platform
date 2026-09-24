<?php

namespace App\Repositories\Contracts;

use App\Models\Protocol;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProtocolRepositoryInterface
{
    /**
     * Paginate protocols with optional search, category, and sort filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a published protocol by its slug or fail with 404.
     */
    public function findBySlugOrFail(string $slug): Protocol;

    /**
     * Retrieve the highest voted published protocols.
     *
     * @return Collection<int, Protocol>
     */
    public function getTopVoted(int $limit = 10): Collection;
}
