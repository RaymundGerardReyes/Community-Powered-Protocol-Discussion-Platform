<?php

use App\Models\Protocol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can retrieve paginated protocols list with meta', function () {
    $user = User::factory()->create();
    Protocol::factory()->count(3)->create([
        'user_id' => $user->id,
        'status' => 'published',
    ]);

    $response = $this->getJson('/api/v1/protocols');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'slug', 'description', 'category', 'status', 'votes_count', 'score', 'average_rating'],
            ],
            'links',
            'meta',
        ]);
});

test('can filter protocols by category', function () {
    $user = User::factory()->create();
    Protocol::factory()->create([
        'user_id' => $user->id,
        'category' => 'DeFi',
        'status' => 'published',
    ]);
    Protocol::factory()->create([
        'user_id' => $user->id,
        'category' => 'Governance',
        'status' => 'published',
    ]);

    $response = $this->getJson('/api/v1/protocols?category=DeFi');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.category'))->toBe('DeFi');
});

test('can retrieve single protocol by slug', function () {
    $user = User::factory()->create();
    $protocol = Protocol::factory()->create([
        'user_id' => $user->id,
        'title' => 'Decentralized Proof Protocol',
        'slug' => 'decentralized-proof-protocol',
        'status' => 'published',
    ]);

    $response = $this->getJson('/api/v1/protocols/decentralized-proof-protocol');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $protocol->id,
                'slug' => 'decentralized-proof-protocol',
                'title' => 'Decentralized Proof Protocol',
            ],
        ]);
});

test('authenticated user can create protocol and auto-generate slug', function () {
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
});

test('unauthenticated user cannot create protocol', function () {
    $response = $this->postJson('/api/v1/protocols', [
        'title' => 'Unauthorized Protocol',
        'description' => 'Should fail',
        'category' => 'DeFi',
    ]);

    $response->assertStatus(401);
});

test('author can update their protocol', function () {
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
});

test('non-author cannot update another users protocol', function () {
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
});

test('author can delete their protocol', function () {
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
});
