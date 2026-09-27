<?php

namespace Tests\Unit\Repositories;

use App\Models\Protocol;
use App\Models\User;
use App\Repositories\Contracts\ProtocolRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ProtocolRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ProtocolRepositoryInterface::class);
    }

    public function test_paginate_with_filters_defaults_to_published_protocols(): void
    {
        $user = User::factory()->create();
        Protocol::factory()->create(['user_id' => $user->id, 'status' => 'published']);
        Protocol::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $result = $this->repository->paginateWithFilters([], 10);

        $this->assertSame(1, $result->total());
        $this->assertSame('published', $result->items()[0]->status);
    }

    public function test_paginate_with_filters_filters_by_category(): void
    {
        $user = User::factory()->create();
        Protocol::factory()->create(['user_id' => $user->id, 'category' => 'DeFi', 'status' => 'published']);
        Protocol::factory()->create(['user_id' => $user->id, 'category' => 'Privacy', 'status' => 'published']);

        $result = $this->repository->paginateWithFilters(['category' => 'DeFi'], 10);

        $this->assertSame(1, $result->total());
        $this->assertSame('DeFi', $result->items()[0]->category);
    }

    public function test_paginate_with_filters_searches_title_and_description(): void
    {
        $user = User::factory()->create();
        Protocol::factory()->create([
            'user_id' => $user->id,
            'title' => 'Solidity Reentrancy Guard Protocol',
            'description' => 'Security standard',
            'status' => 'published',
        ]);
        Protocol::factory()->create([
            'user_id' => $user->id,
            'title' => 'NFT Metadata Standard',
            'description' => 'Metadata format',
            'status' => 'published',
        ]);

        $result = $this->repository->paginateWithFilters(['search' => 'Reentrancy'], 10);

        $this->assertSame(1, $result->total());
        $this->assertSame('Solidity Reentrancy Guard Protocol', $result->items()[0]->title);
    }

    public function test_find_by_slug_or_fail_resolves_slug_and_numeric_id(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $user->id,
            'slug' => 'governance-multisig-v2',
            'title' => 'Governance Multisig v2',
        ]);

        $bySlug = $this->repository->findBySlugOrFail('governance-multisig-v2');
        $this->assertSame($protocol->id, $bySlug->id);

        $byId = $this->repository->findBySlugOrFail((string) $protocol->id);
        $this->assertSame($protocol->id, $byId->id);
    }

    public function test_find_by_slug_or_fail_throws_exception_if_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->findBySlugOrFail('non-existent-protocol-slug');
    }

    public function test_get_top_voted_returns_ordered_collection(): void
    {
        $user = User::factory()->create();
        $p1 = Protocol::factory()->create(['user_id' => $user->id, 'score' => 10, 'status' => 'published']);
        $p2 = Protocol::factory()->create(['user_id' => $user->id, 'score' => 50, 'status' => 'published']);
        $p3 = Protocol::factory()->create(['user_id' => $user->id, 'score' => 30, 'status' => 'published']);

        $top = $this->repository->getTopVoted(2);

        $this->assertCount(2, $top);
        $this->assertSame($p2->id, $top[0]->id);
        $this->assertSame($p3->id, $top[1]->id);
    }
}
