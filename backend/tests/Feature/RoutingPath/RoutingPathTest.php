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

    public function test_response_identifies_database_origin_headers_when_scout_is_null(): void
    {
        config(['scout.driver' => 'null']);

        $user = User::factory()->create();
        Protocol::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $response = $this->getJson('/api/v1/protocols');

        $response->assertStatus(200)
            ->assertHeader('X-Search-Driver', 'null')
            ->assertHeader('X-Data-Source', 'database')
            ->assertHeader('X-Database-Connection')
            ->assertHeader('X-Database-Target');
    }

    public function test_response_strictly_fails_with_503_when_scout_driver_is_typesense_and_client_unreachable(): void
    {
        config(['scout.driver' => 'typesense']);

        $response = $this->getJson('/api/v1/protocols');

        // With mock/unconfigured client in testing, strict mode aborts 503 instead of silently pulling from database
        $response->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    public function test_threads_endpoint_identifies_database_headers(): void
    {
        config(['scout.driver' => 'null']);

        $user = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $user->id]);
        Thread::factory()->create(['protocol_id' => $protocol->id, 'user_id' => $user->id]);

        $response = $this->getJson("/api/v1/protocols/{$protocol->id}/threads");

        $response->assertStatus(200)
            ->assertHeader('X-Search-Driver', 'null')
            ->assertHeader('X-Data-Source', 'database');
    }
}
