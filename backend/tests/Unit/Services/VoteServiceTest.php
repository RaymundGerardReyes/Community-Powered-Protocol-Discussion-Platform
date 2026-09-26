<?php

namespace Tests\Unit\Services;

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VoteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VoteService::class);
    }

    public function test_can_cast_new_upvote_on_protocol(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'votes_count' => 0,
            'score' => 0,
            'reviews_count' => 0,
        ]);

        $result = $this->service->cast($user, 'protocol', $protocol->id, 1);

        $this->assertSame('created', $result['action']);
        $this->assertSame(1, $result['current_vote']);
        $this->assertSame(1, $result['votes_count']);

        $protocol->refresh();
        $this->assertSame(1, $protocol->votes_count);
        $this->assertSame(10, $protocol->score);
    }

    public function test_casting_same_vote_toggles_off(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['votes_count' => 0]);

        // Cast first vote
        $this->service->cast($user, 'thread', $thread->id, 1);
        $this->assertSame(1, $thread->fresh()->votes_count);

        // Cast same vote again -> should toggle off
        $result = $this->service->cast($user, 'thread', $thread->id, 1);

        $this->assertSame('removed', $result['action']);
        $this->assertSame(0, $result['current_vote']);
        $this->assertSame(0, $result['votes_count']);
        $this->assertSame(0, $thread->fresh()->votes_count);
    }

    public function test_can_flip_vote_from_upvote_to_downvote(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['votes_count' => 0]);

        // Upvote
        $this->service->cast($user, 'comment', $comment->id, 1);
        $this->assertSame(1, $comment->fresh()->votes_count);

        // Flip to downvote
        $result = $this->service->cast($user, 'comment', $comment->id, -1);

        $this->assertSame('updated', $result['action']);
        $this->assertSame(-1, $result['current_vote']);
        $this->assertSame(-1, $result['votes_count']);
        $this->assertSame(-1, $comment->fresh()->votes_count);
    }

    public function test_throws_validation_exception_for_invalid_type(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();
        $this->service->cast($user, 'invalid_model_type', 1, 1);
    }
}
