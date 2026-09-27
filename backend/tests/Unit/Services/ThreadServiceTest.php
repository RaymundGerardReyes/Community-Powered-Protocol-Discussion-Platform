<?php

namespace Tests\Unit\Services;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use App\Services\ThreadService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ThreadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ThreadService::class);
    }

    public function test_can_create_discussion_thread_with_unique_slug(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create();

        $thread = $this->service->create($user, $protocol, [
            'title' => 'Gas Optimization Proposal',
            'content' => 'Let us discuss memory layout optimizations.',
            'is_pinned' => true,
        ]);

        $this->assertSame($protocol->id, $thread->protocol_id);
        $this->assertSame($user->id, $thread->user_id);
        $this->assertSame('Gas Optimization Proposal', $thread->title);
        $this->assertStringStartsWith('gas-optimization-proposal-', $thread->slug);
        $this->assertSame('Let us discuss memory layout optimizations.', $thread->content);
        $this->assertTrue($thread->is_pinned);
        $this->assertDatabaseHas('threads', ['id' => $thread->id]);
    }

    public function test_can_increment_views_counter(): void
    {
        $thread = Thread::factory()->create(['views_count' => 5]);

        $this->service->incrementViews($thread);

        $this->assertSame(6, $thread->fresh()->views_count);
    }

    public function test_author_can_update_thread(): void
    {
        $author = User::factory()->create();
        $thread = Thread::factory()->create([
            'user_id' => $author->id,
            'title' => 'Original Thread Title',
        ]);

        $updated = $this->service->update($thread, $author, [
            'title' => 'Updated Thread Title',
            'content' => 'Revised thread body.',
        ]);

        $this->assertSame('Updated Thread Title', $updated->title);
        $this->assertSame('Revised thread body.', $updated->fresh()->content);
    }

    public function test_non_author_cannot_update_thread(): void
    {
        $this->expectException(AuthorizationException::class);

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $thread = Thread::factory()->create(['user_id' => $author->id]);

        $this->service->update($thread, $otherUser, [
            'title' => 'Unauthorized Modification',
        ]);
    }

    public function test_author_can_delete_thread(): void
    {
        $author = User::factory()->create();
        $thread = Thread::factory()->create(['user_id' => $author->id]);

        $deleted = $this->service->delete($thread, $author);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('threads', ['id' => $thread->id]);
    }

    public function test_non_author_cannot_delete_thread(): void
    {
        $this->expectException(AuthorizationException::class);

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $thread = Thread::factory()->create(['user_id' => $author->id]);

        $this->service->delete($thread, $otherUser);
    }
}
