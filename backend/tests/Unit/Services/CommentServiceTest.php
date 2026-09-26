<?php

namespace Tests\Unit\Services;

use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommentService::class);
    }

    public function test_can_create_root_comment_and_increments_thread_replies_count(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['replies_count' => 0]);

        $comment = $this->service->create($user, $thread, [
            'content' => 'First discussion comment on protocol architecture.',
        ]);

        $this->assertSame($thread->id, $comment->thread_id);
        $this->assertSame($user->id, $comment->user_id);
        $this->assertSame('First discussion comment on protocol architecture.', $comment->content);
        $this->assertNull($comment->parent_id);

        $this->assertSame(1, $thread->fresh()->replies_count);
    }

    public function test_can_create_nested_reply_to_existing_comment(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['replies_count' => 1]);
        $parentComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'content' => 'Parent thought.',
        ]);

        $reply = $this->service->create($user, $thread, [
            'content' => 'Nested reply supporting parent thought.',
            'parent_id' => $parentComment->id,
        ]);

        $this->assertSame($parentComment->id, $reply->parent_id);
        $this->assertSame(2, $thread->fresh()->replies_count);
    }

    public function test_throws_validation_exception_if_parent_belongs_to_different_thread(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();
        $thread1 = Thread::factory()->create();
        $thread2 = Thread::factory()->create();
        $parentOnThread1 = Comment::factory()->create(['thread_id' => $thread1->id]);

        $this->service->create($user, $thread2, [
            'content' => 'Cross-thread comment attempt',
            'parent_id' => $parentOnThread1->id,
        ]);
    }

    public function test_author_can_delete_comment_and_decrements_replies_count(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['replies_count' => 1]);
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $result = $this->service->delete($comment, $user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertSame(0, $thread->fresh()->replies_count);
    }

    public function test_non_author_cannot_delete_comment(): void
    {
        $this->expectException(AuthorizationException::class);

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $thread = Thread::factory()->create(['replies_count' => 1]);
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $this->service->delete($comment, $otherUser);
    }
}
