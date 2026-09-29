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
}
