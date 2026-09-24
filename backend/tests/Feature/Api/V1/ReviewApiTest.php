<?php

use App\Models\Protocol;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can retrieve paginated reviews list for protocol', function () {
    $protocol = Protocol::factory()->create();
    Review::factory()->count(3)->create(['protocol_id' => $protocol->id]);

    $response = $this->getJson("/api/v1/protocols/{$protocol->id}/reviews");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'protocol_id', 'rating', 'verdict', 'summary', 'findings', 'author'],
            ],
            'meta',
        ]);
});

test('authenticated peer reviewer can submit review and update protocol rating metrics', function () {
    $author = User::factory()->create();
    $reviewer = User::factory()->create();
    $protocol = Protocol::factory()->create([
        'user_id' => $author->id,
        'reviews_count' => 0,
        'average_rating' => 0.00,
    ]);

    $response = $this->actingAs($reviewer, 'sanctum')
        ->postJson("/api/v1/protocols/{$protocol->id}/reviews", [
            'rating' => 5,
            'verdict' => 'approved',
            'summary' => 'Formally verified smart contract logic.',
            'findings' => 'No reentrancy vulnerabilities discovered during fuzzing.',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'rating' => 5,
                'verdict' => 'approved',
                'summary' => 'Formally verified smart contract logic.',
            ],
        ]);

    $protocol->refresh();
    expect($protocol->reviews_count)->toBe(1)
        ->and((float) $protocol->average_rating)->toBe(5.00);
});

test('protocol author cannot submit review on their own protocol', function () {
    $author = User::factory()->create();
    $protocol = Protocol::factory()->create(['user_id' => $author->id]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson("/api/v1/protocols/{$protocol->id}/reviews", [
            'rating' => 5,
            'verdict' => 'approved',
            'summary' => 'Self praise review',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['protocol_id']);
});

test('user cannot submit duplicate reviews on the same protocol', function () {
    $author = User::factory()->create();
    $reviewer = User::factory()->create();
    $protocol = Protocol::factory()->create(['user_id' => $author->id]);

    Review::factory()->create([
        'protocol_id' => $protocol->id,
        'user_id' => $reviewer->id,
        'rating' => 4,
    ]);

    $response = $this->actingAs($reviewer, 'sanctum')
        ->postJson("/api/v1/protocols/{$protocol->id}/reviews", [
            'rating' => 5,
            'verdict' => 'approved',
            'summary' => 'Second review attempt',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['protocol_id']);
});

test('average rating updates accurately when multiple reviews exist', function () {
    $author = User::factory()->create();
    $reviewer1 = User::factory()->create();
    $reviewer2 = User::factory()->create();
    $protocol = Protocol::factory()->create(['user_id' => $author->id]);

    Review::factory()->create([
        'protocol_id' => $protocol->id,
        'user_id' => $reviewer1->id,
        'rating' => 4,
    ]);

    $this->actingAs($reviewer2, 'sanctum')
        ->postJson("/api/v1/protocols/{$protocol->id}/reviews", [
            'rating' => 2,
            'verdict' => 'changes_requested',
            'summary' => 'Gas optimization required.',
        ])->assertStatus(201);

    $protocol->refresh();
    expect($protocol->reviews_count)->toBe(2)
        ->and((float) $protocol->average_rating)->toBe(3.00);
});

test('deleting a review recalculates protocol review aggregates', function () {
    $author = User::factory()->create();
    $reviewer = User::factory()->create();
    $protocol = Protocol::factory()->create(['user_id' => $author->id]);

    $review = Review::factory()->create([
        'protocol_id' => $protocol->id,
        'user_id' => $reviewer->id,
        'rating' => 5,
    ]);

    expect($protocol->fresh()->reviews_count)->toBe(1);

    $response = $this->actingAs($reviewer, 'sanctum')
        ->deleteJson("/api/v1/reviews/{$review->id}");

    $response->assertStatus(200);

    $protocol->refresh();
    expect($protocol->reviews_count)->toBe(0)
        ->and((float) $protocol->average_rating)->toBe(0.00);
});
