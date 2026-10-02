<?php

namespace Tests\Feature\Api\V1;

use App\Models\Protocol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Prevent Scout from attempting remote HTTP indexing during factory writes
        config(['scout.driver' => 'null']);
    }

    protected function mockTypesenseClient(array $hits = [], int $found = 1): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->any())
            ->method('search')
            ->willReturn([
                'found' => $found,
                'hits' => $hits,
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

        $this->app->instance(\Typesense\Client::class, $client);
    }

    public function test_can_retrieve_paginated_protocols_list_with_meta(): void
    {
        $this->mockTypesenseClient([
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
        ], 1);

        $response = $this->getJson('/api/v1/protocols');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'slug', 'description', 'content', 'category', 'tags', 'status', 'votes_count', 'score', 'average_rating', 'rating'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.title', 'Decentralized ZK Rollup')
            ->assertJsonPath('data.0.slug', 'decentralized-zk-rollup');
    }

    public function test_protocols_endpoint_strictly_aborts_503_when_typesense_unconfigured(): void
    {
        config(['scout.typesense.is_configured' => false]);
        $this->app->forgetInstance(\Typesense\Client::class);
        $this->app->instance(\Typesense\Client::class, null);

        $response = $this->getJson('/api/v1/protocols');

        $response->assertStatus(503);
        $this->assertStringContainsString('permanently removed', (string) $response->json('message'));
    }

    public function test_can_filter_protocols_by_category(): void
    {
        $this->mockTypesenseClient([
            [
                'document' => [
                    'id' => '10',
                    'title' => 'Uniswap DeFi Core',
                    'slug' => 'uniswap-defi-core',
                    'category' => 'DeFi',
                    'status' => 'published',
                    'score' => 50,
                    'votes_count' => 50,
                    'author' => 'Hayden Adams',
                ],
            ],
        ], 1);

        $response = $this->getJson('/api/v1/protocols?category=defi');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.category', 'DeFi');
    }

    public function test_can_search_protocols(): void
    {
        $this->mockTypesenseClient([
            [
                'document' => [
                    'id' => '20',
                    'title' => 'Decentralized ZK Rollup Protocol',
                    'slug' => 'decentralized-zk-rollup-protocol',
                    'description' => 'Scalable execution layer',
                    'category' => 'DeFi',
                    'status' => 'published',
                    'author' => 'Rollup Dev',
                ],
            ],
        ], 1);

        $response = $this->getJson('/api/v1/protocols?search=rollup');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Decentralized ZK Rollup Protocol');
    }

    public function test_can_retrieve_single_protocol_by_slug(): void
    {
        $this->mockTypesenseClient([
            [
                'document' => [
                    'id' => '30',
                    'title' => 'Decentralized Proof Protocol',
                    'slug' => 'decentralized-proof-protocol',
                    'description' => 'Proof standard',
                    'category' => 'Cryptography',
                    'status' => 'published',
                    'author' => 'Proof Author',
                ],
            ],
        ], 1);

        $response = $this->getJson('/api/v1/protocols/decentralized-proof-protocol');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 30,
                    'slug' => 'decentralized-proof-protocol',
                    'title' => 'Decentralized Proof Protocol',
                ],
            ]);
    }

    public function test_authenticated_user_can_create_protocol_and_auto_generate_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/protocols', [
                'title' => 'Zero Knowledge Consensus v2',
                'description' => 'A scalable zero-knowledge consensus architecture.',
                'category' => 'Cryptography',
                'version' => '2.0.0',
                'status' => 'published',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Zero Knowledge Consensus v2',
                    'slug' => 'zero-knowledge-consensus-v2',
                    'category' => 'Cryptography',
                    'status' => 'published',
                ],
            ]);

        $this->assertDatabaseHas('protocols', [
            'user_id' => $user->id,
            'slug' => 'zero-knowledge-consensus-v2',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_protocol(): void
    {
        $response = $this->postJson('/api/v1/protocols', [
            'title' => 'Unauthorized Protocol',
            'description' => 'Should fail',
            'category' => 'DeFi',
        ]);

        $response->assertStatus(401);
    }

    public function test_author_can_update_their_protocol(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/protocols/{$protocol->id}", [
                'title' => 'Updated Protocol Title',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Protocol Title',
                    'slug' => 'updated-protocol-title',
                ],
            ]);
    }

    public function test_non_author_cannot_update_another_users_protocol(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $author->id,
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/protocols/{$protocol->id}", [
                'title' => 'Malicious Update',
            ]);

        $response->assertStatus(403);
    }

    public function test_author_can_delete_their_protocol(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/protocols/{$protocol->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Protocol deleted successfully',
            ]);

        $this->assertDatabaseMissing('protocols', [
            'id' => $protocol->id,
        ]);
    }

    public function test_can_fetch_protocol_categories_dynamically(): void
    {
        Protocol::factory()->create(['category' => 'Gut Health', 'status' => 'published']);
        Protocol::factory()->create(['category' => 'Sleep', 'status' => 'published']);

        $response = $this->getJson('/api/v1/protocols/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['name', 'slug', 'count'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }
}
