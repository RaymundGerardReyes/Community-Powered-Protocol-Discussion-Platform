<?php

namespace Tests\Feature\RoutingPath;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_protocols_endpoint_strictly_fails_with_503_when_typesense_is_unconfigured(): void
    {
        config(['scout.typesense.is_configured' => false]);
        $this->app->forgetInstance(\Typesense\Client::class);
        $this->app->instance(\Typesense\Client::class, null);

        $response = $this->getJson('/api/v1/protocols');

        $response->assertStatus(503);
        $this->assertStringContainsString('permanently removed', (string) $response->json('message'));
    }

    public function test_response_strictly_fails_with_503_when_scout_driver_is_typesense_and_client_unreachable(): void
    {
        config(['scout.driver' => 'typesense']);

        $mockCollections = $this->createMock(\Typesense\Collections::class);
        $mockCollections->expects($this->any())
            ->method('offsetGet')
            ->willThrowException(new \RuntimeException('Connection refused to Typesense cluster'));

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $this->app->instance(\Typesense\Client::class, $client);

        $response = $this->getJson('/api/v1/protocols');

        $response->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    public function test_protocols_endpoint_hydrates_directly_from_typesense_documents_without_sql_queries(): void
    {
        config(['scout.driver' => 'typesense']);

        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->any())
            ->method('search')
            ->willReturn([
                'found' => 1,
                'hits' => [
                    [
                        'document' => [
                            'id' => '42',
                            'title' => 'Live Typesense Protocol',
                            'slug' => 'live-typesense-protocol',
                            'description' => 'Directly from Typesense Cloud',
                            'category' => 'DeFi',
                            'status' => 'published',
                            'score' => 99,
                            'votes_count' => 99,
                            'reviews_count' => 5,
                            'average_rating' => 4.8,
                            'author' => 'Typesense Validator',
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
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $this->app->instance(\Typesense\Client::class, $client);

        // Count database queries - should be 0 because we hydrate directly from Typesense
        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->getJson('/api/v1/protocols');

        $response->assertStatus(200)
            ->assertHeader('X-Search-Driver', 'typesense')
            ->assertHeader('X-Data-Source', 'typesense')
            ->assertJsonPath('data.0.title', 'Live Typesense Protocol')
            ->assertJsonPath('data.0.slug', 'live-typesense-protocol')
            ->assertJsonPath('data.0.author.name', 'Typesense Validator');

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        $this->assertCount(0, $queries, 'Protocols endpoint should not execute any SQL queries when Typesense is active');
    }

    public function test_threads_endpoint_strictly_fails_with_503_when_typesense_is_unconfigured(): void
    {
        $this->app->forgetInstance(\Typesense\Client::class);
        $this->app->instance(\Typesense\Client::class, null);

        $response = $this->getJson('/api/v1/protocols/1/threads');

        $response->assertStatus(503);
        $this->assertStringContainsString('permanently removed', (string) $response->json('message'));
    }

    public function test_threads_endpoint_hydrates_directly_from_typesense_documents_without_sql_queries(): void
    {
        config(['scout.driver' => 'typesense']);

        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->any())
            ->method('search')
            ->willReturn([
                'found' => 1,
                'hits' => [
                    [
                        'document' => [
                            'id' => '10',
                            'protocol_id' => 1,
                            'user_id' => 1,
                            'title' => 'Typesense Live Discussion',
                            'body' => 'Hydrated directly from Typesense without SQL',
                            'content' => 'Hydrated directly from Typesense without SQL',
                            'author' => 'Thread Validator',
                            'replies_count' => 0,
                            'votes_count' => 5,
                            'is_pinned' => false,
                            'is_locked' => false,
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
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $this->app->instance(\Typesense\Client::class, $client);

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->getJson('/api/v1/protocols/1/threads');

        $response->assertStatus(200)
            ->assertHeader('X-Search-Driver', 'typesense')
            ->assertHeader('X-Data-Source', 'typesense')
            ->assertJsonPath('data.0.title', 'Typesense Live Discussion')
            ->assertJsonPath('data.0.author.name', 'Thread Validator');

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        $this->assertCount(0, $queries, 'Threads endpoint should not execute any SQL queries when Typesense is active');
    }
}
