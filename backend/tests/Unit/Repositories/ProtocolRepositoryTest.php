<?php

namespace Tests\Unit\Repositories;

use App\Repositories\Eloquent\ProtocolRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProtocolRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_strictly_aborts_with_503_when_typesense_is_unconfigured(): void
    {
        $repo = new ProtocolRepository(null);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('permanently removed');

        $repo->paginateWithFilters([], 10);
    }

    public function test_paginate_with_filters_queries_typesense_and_hydrates_protocol_models(): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params) {
                return $params['q'] === '*'
                    && str_contains($params['filter_by'], 'status:=published')
                    && $params['per_page'] === 10;
            }))
            ->willReturn([
                'found' => 1,
                'hits' => [
                    [
                        'document' => [
                            'id' => '1',
                            'title' => 'Decentralized ZK Rollup',
                            'slug' => 'decentralized-zk-rollup',
                            'description' => 'A scalable zero-knowledge rollup.',
                            'category' => 'DeFi',
                            'status' => 'published',
                            'score' => 42,
                            'votes_count' => 42,
                            'reviews_count' => 3,
                            'average_rating' => 4.5,
                            'author' => 'Satoshi Nakamoto',
                            'tags' => ['zk', 'rollup'],
                            'created_at' => 1727654400,
                        ],
                    ],
                ],
            ]);

        $mockCollection = $this->createMock(\Typesense\Collection::class);
        $mockCollection->documents = $mockDocuments;

        $mockCollections = $this->createMock(\Typesense\Collections::class);
        $mockCollections->expects($this->any())
            ->method('offsetGet')
            ->with('protocol')
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $repo = new ProtocolRepository($client);
        $result = $repo->paginateWithFilters([], 10);

        $this->assertSame(1, $result->total());
        $this->assertSame('published', $result->items()[0]->status);
        $this->assertSame('Decentralized ZK Rollup', $result->items()[0]->title);
        $this->assertSame('Satoshi Nakamoto', $result->items()[0]->user->name);
    }

    public function test_find_by_slug_or_fail_retrieves_from_typesense(): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params) {
                return $params['q'] === 'decentralized-zk-rollup'
                    && str_contains($params['filter_by'], 'slug:=decentralized-zk-rollup');
            }))
            ->willReturn([
                'found' => 1,
                'hits' => [
                    [
                        'document' => [
                            'id' => '99',
                            'title' => 'Decentralized ZK Rollup',
                            'slug' => 'decentralized-zk-rollup',
                            'description' => 'Retrieved via slug search',
                            'category' => 'DeFi',
                            'status' => 'published',
                            'score' => 10,
                            'author' => 'Vitalik',
                        ],
                    ],
                ],
            ]);

        $mockCollection = $this->createMock(\Typesense\Collection::class);
        $mockCollection->documents = $mockDocuments;

        $mockCollections = $this->createMock(\Typesense\Collections::class);
        $mockCollections->expects($this->any())
            ->method('offsetGet')
            ->with('protocol')
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $repo = new ProtocolRepository($client);
        $protocol = $repo->findBySlugOrFail('decentralized-zk-rollup');

        $this->assertSame(99, $protocol->id);
        $this->assertSame('decentralized-zk-rollup', $protocol->slug);
        $this->assertSame('Vitalik', $protocol->user->name);
    }

    public function test_find_by_slug_or_fail_aborts_404_when_hit_missing(): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->any())
            ->method('search')
            ->willReturn([
                'found' => 0,
                'hits' => [],
            ]);

        $mockCollection = $this->createMock(\Typesense\Collection::class);
        $mockCollection->documents = $mockDocuments;

        $mockCollections = $this->createMock(\Typesense\Collections::class);
        $mockCollections->expects($this->any())
            ->method('offsetGet')
            ->with('protocol')
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $repo = new ProtocolRepository($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Protocol not found');

        $repo->findBySlugOrFail('non-existent-protocol');
    }

    public function test_get_top_voted_queries_typesense_ordered_by_votes(): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params) {
                return $params['sort_by'] === 'votes_count:desc'
                    && $params['per_page'] === 2;
            }))
            ->willReturn([
                'found' => 2,
                'hits' => [
                    [
                        'document' => [
                            'id' => '1',
                            'title' => 'Top Protocol 1',
                            'slug' => 'top-protocol-1',
                            'votes_count' => 100,
                            'score' => 100,
                            'status' => 'published',
                        ],
                    ],
                    [
                        'document' => [
                            'id' => '2',
                            'title' => 'Top Protocol 2',
                            'slug' => 'top-protocol-2',
                            'votes_count' => 80,
                            'score' => 80,
                            'status' => 'published',
                        ],
                    ],
                ],
            ]);

        $mockCollection = $this->createMock(\Typesense\Collection::class);
        $mockCollection->documents = $mockDocuments;

        $mockCollections = $this->createMock(\Typesense\Collections::class);
        $mockCollections->expects($this->any())
            ->method('offsetGet')
            ->with('protocol')
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $repo = new ProtocolRepository($client);
        $top = $repo->getTopVoted(2);

        $this->assertCount(2, $top);
        $this->assertSame(1, $top[0]->id);
        $this->assertSame(2, $top[1]->id);
    }
}
