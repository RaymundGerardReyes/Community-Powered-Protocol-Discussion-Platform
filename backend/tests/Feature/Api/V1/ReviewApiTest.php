<?php

namespace Tests\Feature\Api\V1;

use App\Models\Protocol;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'null']);
    }

    public function test_can_retrieve_paginated_reviews_list_for_protocol(): void
    {
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
    }

    public function test_authenticated_peer_reviewer_can_submit_review_and_update_protocol_rating_metrics(): void
    {
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
        $this->assertSame(1, $protocol->reviews_count);
        $this->assertEquals(5.00, (float) $protocol->average_rating);
    }

    public function test_protocol_author_cannot_submit_review_on_their_own_protocol(): void
    {
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
    }

    public function test_user_cannot_submit_duplicate_reviews_on_the_same_protocol(): void
    {
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
    }

    public function test_average_rating_updates_accurately_when_multiple_reviews_exist(): void
    {
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
        $this->assertSame(2, $protocol->reviews_count);
        $this->assertEquals(3.00, (float) $protocol->average_rating);
    }

    public function test_deleting_a_review_recalculates_protocol_review_aggregates(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $review = Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
        ]);

        $this->assertSame(1, $protocol->fresh()->reviews_count);

        $response = $this->actingAs($reviewer, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(200);

        $protocol->refresh();
        $this->assertSame(0, $protocol->reviews_count);
        $this->assertEquals(0.00, (float) $protocol->average_rating);
    }

    public function test_can_submit_review_with_optional_feedback_and_rating(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($reviewer, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocol->id}/reviews", [
                'rating' => 4,
                'feedback' => 'Great protocol for sleep recovery. Clear instructions.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'rating' => 4,
                    'feedback' => 'Great protocol for sleep recovery. Clear instructions.',
                    'verdict' => 'approved',
                ],
            ]);
    }

    public function test_authenticated_peer_reviewer_can_update_own_review(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);
        $review = Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'summary' => 'Initial review summary',
        ]);

        $response = $this->actingAs($reviewer, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 5,
                'summary' => 'Updated review with full endorsement',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.summary', 'Updated review with full endorsement');

        $this->assertSame(5, $review->fresh()->rating);
    }

    public function test_user_cannot_update_others_review(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $stranger = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);
        $review = Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", [
                'summary' => 'Malicious overwrite attempt',
            ]);

        $response->assertStatus(403);
    }
}
