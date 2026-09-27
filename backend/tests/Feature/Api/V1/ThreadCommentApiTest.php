<?php

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can retrieve paginated threads list for protocol', function () {
    $protocol = Protocol::factory()->create();
    Thread::factory()->count(3)->create(['protocol_id' => $protocol->id]);

    $response = $this->getJson("/api/v1/protocols/{$protocol->id}/threads");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'protocol_id', 'title', 'slug', 'content', 'views_count', 'replies_count'],
            ],
            'meta',
        ]);
});

test('can view a thread and increment its view count', function () {
    $thread = Thread::factory()->create(['views_count' => 5]);

    $response = $this->getJson("/api/v1/threads/{$thread->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $thread->id,
                'title' => $thread->title,
            ],
        ]);

    expect($thread->fresh()->views_count)->toBe(6);
});

test('authenticated user can create thread in protocol', function () {
    $user = User::factory()->create();
    $protocol = Protocol::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/protocols/{$protocol->id}/threads", [
            'title' => 'Proposal Discussion for Tokenomics',
            'content' => 'What are your thoughts on bonding curve parameters?',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'title' => 'Proposal Discussion for Tokenomics',
                'protocol_id' => $protocol->id,
            ],
        ]);

    $this->assertDatabaseHas('threads', [
        'protocol_id' => $protocol->id,
        'user_id' => $user->id,
        'title' => 'Proposal Discussion for Tokenomics',
    ]);
});

test('authenticated user can post comment and nested reply to thread', function () {
    $user = User::factory()->create();
    $thread = Thread::factory()->create(['replies_count' => 0]);

    // Top-level comment
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/comments", [
            'content' => 'I strongly agree with the gas reduction section.',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'thread_id' => $thread->id,
                'content' => 'I strongly agree with the gas reduction section.',
            ],
        ]);

    $commentId = $response->json('data.id');
    expect($thread->fresh()->replies_count)->toBe(1);

    // Nested reply to top-level comment
    $replyResponse = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/comments", [
            'content' => 'Here is benchmark data supporting this.',
            'parent_id' => $commentId,
        ]);

    $replyResponse->assertStatus(201)
        ->assertJson([
            'data' => [
                'parent_id' => $commentId,
                'content' => 'Here is benchmark data supporting this.',
            ],
        ]);

    expect($thread->fresh()->replies_count)->toBe(2);
});

test('user cannot reply to a parent comment from another thread', function () {
    $user = User::factory()->create();
    $thread1 = Thread::factory()->create();
    $thread2 = Thread::factory()->create();

    $commentOnThread1 = Comment::factory()->create(['thread_id' => $thread1->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/threads/{$thread2->id}/comments", [
            'content' => 'Mismatched reply',
            'parent_id' => $commentOnThread1->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('author can delete their comment and decrement thread replies count', function () {
    $user = User::factory()->create();
    $thread = Thread::factory()->create(['replies_count' => 1]);
    $comment = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/comments/{$comment->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    expect($thread->fresh()->replies_count)->toBe(0);
});

test('returns recursively assembled comment tree with arbitrary depth and ISO timestamps', function () {
    $thread = Thread::factory()->create();

    $zuck = User::factory()->create(['name' => 'Mr. Zuckerberg']);
    $bezos = User::factory()->create(['name' => 'Mr. Bezos']);
    $musk = User::factory()->create(['name' => 'Mr. Musk']);
    $gates = User::factory()->create(['name' => 'Mr. Gates']);
    $jobs = User::factory()->create(['name' => 'Mr. Jobs']);

    // A: Zuckerberg (root)
    $commentA = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $zuck->id,
        'parent_id' => null,
        'content' => 'Original comment from Zuckerberg',
        'created_at' => '2026-09-27 15:14:42',
    ]);

    // B: Bezos (reply to A)
    $commentB = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $bezos->id,
        'parent_id' => $commentA->id,
        'content' => 'Reply from Bezos',
        'created_at' => '2026-09-27 15:15:07',
    ]);

    // C: Musk (reply to B)
    $commentC = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $musk->id,
        'parent_id' => $commentB->id,
        'content' => 'Reply from Musk',
        'created_at' => '2026-09-27 15:15:41',
    ]);

    // D: Gates (reply to C)
    $commentD = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $gates->id,
        'parent_id' => $commentC->id,
        'content' => 'Reply from Gates',
        'created_at' => '2026-09-27 15:16:03',
    ]);

    // E: Jobs (reply to A)
    $commentE = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $jobs->id,
        'parent_id' => $commentA->id,
        'content' => 'Reply from Jobs',
        'created_at' => '2026-09-27 15:17:12',
    ]);

    $response = $this->getJson("/api/v1/threads/{$thread->id}/comments");

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['author']['name'])->toBe('Mr. Zuckerberg');
    expect($data[0]['created_at'])->toContain('2026-09-27T15:14:42');

    // Zuckerberg has 2 direct replies: Bezos and Jobs
    $aReplies = $data[0]['replies'];
    expect($aReplies)->toHaveCount(2);
    expect($aReplies[0]['author']['name'])->toBe('Mr. Bezos');
    expect($aReplies[1]['author']['name'])->toBe('Mr. Jobs');

    // Bezos has 1 reply: Musk
    $bReplies = $aReplies[0]['replies'];
    expect($bReplies)->toHaveCount(1);
    expect($bReplies[0]['author']['name'])->toBe('Mr. Musk');

    // Musk has 1 reply: Gates
    $cReplies = $bReplies[0]['replies'];
    expect($cReplies)->toHaveCount(1);
    expect($cReplies[0]['author']['name'])->toBe('Mr. Gates');

    // Gates has 0 replies
    expect($cReplies[0]['replies'])->toBeEmpty();

    // Jobs has 0 replies
    expect($aReplies[1]['replies'])->toBeEmpty();
});
