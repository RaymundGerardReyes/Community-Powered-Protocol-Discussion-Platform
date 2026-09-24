<?php

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can upvote a protocol and update its score', function () {
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
    expect($protocol->votes_count)->toBe(1)
        ->and($protocol->score)->toBe(10);
});

test('casting identical vote toggles off and removes the vote', function () {
    $user = User::factory()->create();
    $protocol = Protocol::factory()->create(['votes_count' => 0]);

    // Initial upvote
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/votes', [
            'votable_type' => 'protocol',
            'votable_id' => $protocol->id,
            'value' => 1,
        ])->assertStatus(200);

    expect($protocol->fresh()->votes_count)->toBe(1);

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

    expect($protocol->fresh()->votes_count)->toBe(0);
});

test('user can flip vote from upvote to downvote', function () {
    $user = User::factory()->create();
    $protocol = Protocol::factory()->create(['votes_count' => 0]);

    // Upvote
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/votes', [
            'votable_type' => 'protocol',
            'votable_id' => $protocol->id,
            'value' => 1,
        ])->assertStatus(200);

    expect($protocol->fresh()->votes_count)->toBe(1);

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

    expect($protocol->fresh()->votes_count)->toBe(-1);
});

test('user can cast vote on a thread', function () {
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

    expect($thread->fresh()->votes_count)->toBe(1);
});

test('user can cast vote on a comment', function () {
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

    expect($comment->fresh()->votes_count)->toBe(1);
});

test('unauthenticated user cannot vote', function () {
    $protocol = Protocol::factory()->create();

    $response = $this->postJson('/api/v1/votes', [
        'votable_type' => 'protocol',
        'votable_id' => $protocol->id,
        'value' => 1,
    ]);

    $response->assertStatus(401);
});
