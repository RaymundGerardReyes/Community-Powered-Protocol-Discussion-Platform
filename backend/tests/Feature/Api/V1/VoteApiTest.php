<?php

namespace Tests\Feature\Api\V1;

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'null']);
    }

    public function test_authenticated_user_can_upvote_a_protocol_and_update_its_score(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'votes_count' => 0,
            'score' => 0,
            'reviews_count' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocol->id,
                'value' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'action' => 'created',
                    'current_vote' => 1,
                    'votes_count' => 1,
                ],
            ]);

        $protocol->refresh();
        $this->assertSame(1, $protocol->votes_count);
        $this->assertSame(10, $protocol->score);
    }

    public function test_casting_identical_vote_toggles_off_and_removes_the_vote(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create(['votes_count' => 0]);

        // Initial upvote
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocol->id,
                'value' => 1,
            ])->assertStatus(200);

        $this->assertSame(1, $protocol->fresh()->votes_count);

        // Toggle off by clicking upvote again
        $toggleResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocol->id,
                'value' => 1,
            ]);

        $toggleResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'action' => 'removed',
                    'current_vote' => 0,
                    'votes_count' => 0,
                ],
            ]);

        $this->assertSame(0, $protocol->fresh()->votes_count);
    }

    public function test_user_can_flip_vote_from_upvote_to_downvote(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create(['votes_count' => 0]);

        // Upvote
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocol->id,
                'value' => 1,
            ])->assertStatus(200);

        $this->assertSame(1, $protocol->fresh()->votes_count);

        // Flip to downvote (-1)
        $flipResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocol->id,
                'value' => -1,
            ]);

        $flipResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'action' => 'updated',
                    'current_vote' => -1,
                    'votes_count' => -1,
                ],
            ]);

        $this->assertSame(-1, $protocol->fresh()->votes_count);
    }

    public function test_user_can_cast_vote_on_a_thread(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['votes_count' => 0]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'thread',
                'votable_id' => $thread->id,
                'value' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'action' => 'created',
                    'current_vote' => 1,
                    'votes_count' => 1,
                ],
            ]);

        $this->assertSame(1, $thread->fresh()->votes_count);
    }

    public function test_user_can_cast_vote_on_a_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['votes_count' => 0]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'comment',
                'votable_id' => $comment->id,
                'value' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'action' => 'created',
                    'current_vote' => 1,
                    'votes_count' => 1,
                ],
            ]);

        $this->assertSame(1, $comment->fresh()->votes_count);
    }

    public function test_unauthenticated_user_cannot_vote(): void
    {
        $protocol = Protocol::factory()->create();

        $response = $this->postJson('/api/v1/votes', [
            'votable_type' => 'protocol',
            'votable_id' => $protocol->id,
            'value' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_retrieve_active_votes_map(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create();
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create();

        // Cast votes across multiple entities
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/votes', [
            'votable_type' => 'protocol',
            'votable_id' => $protocol->id,
            'value' => 1,
        ]);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/votes', [
            'votable_type' => 'thread',
            'votable_id' => $thread->id,
            'value' => -1,
        ]);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/votes', [
            'votable_type' => 'comment',
            'votable_id' => $comment->id,
            'value' => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/votes/me');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'protocol' => [
                        (string) $protocol->id => 1,
                    ],
                    'thread' => [
                        (string) $thread->id => -1,
                    ],
                    'comment' => [
                        (string) $comment->id => 1,
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_my_votes(): void
    {
        $response = $this->getJson('/api/v1/votes/me');
        $response->assertStatus(401);
    }
}
