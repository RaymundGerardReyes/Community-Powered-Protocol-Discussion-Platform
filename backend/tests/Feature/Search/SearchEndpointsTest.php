<?php

namespace Tests\Feature\Search;

use Tests\TestCase;

class SearchEndpointsTest extends TestCase
{
    public function test_search_status_endpoint_returns_cluster_configuration(): void
    {
        $response = $this->getJson('/api/v1/search/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'scout_driver',
                'is_typesense_active',
                'typesense' => [
                    'host',
                    'port',
                    'protocol',
                    'has_api_key',
                ],
                'timestamp',
            ]);
    }

    public function test_search_reindex_endpoint_triggers_reindexing(): void
    {
        $response = $this->postJson('/api/v1/search/reindex');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'model' => 'all',
            ]);
    }

    public function test_api_response_attaches_search_driver_and_data_source_headers(): void
    {
        $response = $this->getJson('/api/v1/protocols');

        $response->assertHeader('X-Search-Driver');
        $response->assertHeader('X-Data-Source');
    }

    public function test_typesense_strict_routing_aborts_when_configured_driver_is_typesense_and_client_fails(): void
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

        // With failing client, strict mode aborts 503 instead of pulling silently from DB
        $response->assertStatus(503);
    }
}
