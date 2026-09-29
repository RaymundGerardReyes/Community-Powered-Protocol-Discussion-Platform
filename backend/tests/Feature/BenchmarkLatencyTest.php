<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenchmarkLatencyTest extends TestCase
{
    use RefreshDatabase;

    protected function mockTypesenseClient(): void
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

        $this->app->instance(\Typesense\Client::class, $client);
    }

    public function test_track_response_time_middleware_attaches_headers(): void
    {
        $this->mockTypesenseClient();

        $response = $this->getJson('/api/v1/protocols');

        $response->assertOk();
        $response->assertHeader('X-Response-Time');
        $response->assertHeader('Server-Timing');

        $this->assertMatchesRegularExpression('/^[0-9]+(\.[0-9]+)?ms$/', (string) $response->headers->get('X-Response-Time'));
        $this->assertStringContainsString('app;dur=', (string) $response->headers->get('Server-Timing'));
    }

    public function test_benchmark_latency_artisan_command_executes_and_reports_metrics(): void
    {
        $this->mockTypesenseClient();

        $this->artisan('benchmark:latency', [
            '--endpoint' => '/api/v1/protocols',
            '--count' => 10,
        ])->assertSuccessful();
    }
}
